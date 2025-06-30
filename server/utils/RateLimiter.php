<?php
/**
 * Rate Limiter Advanced
 * 
 * Sistema avanzato per limitare il numero di richieste a servizi esterni,
 * con supporto per backoff esponenziale, monitoring dettagliato e gestione
 * adattiva dei limiti di richiesta.
 */

class RateLimiter {
    /**
     * @var string La directory dove i file di rate limit sono memorizzati
     */
    private $storageDir;
    
    /**
     * @var int Granularità conteggio in secondi
     */
    private $granularity = 60; // Default: 1 minuto
    
    /**
     * @var bool Logging delle richieste
     */
    private $enableLogging = true;
    
    /**
     * @var array Cache in-memory per ridurre gli accessi al disco
     */
    private $usageCache = [];
    
    /**
     * @var array Cache dei limiti di servizio
     */
    private $limitsCache = [];
    
    /**
     * @var array Strategie di backoff per ogni servizio
     */
    private $backoffStrategies = [
        'default' => [
            'base' => 1, // Tempo base
            'factor' => 2, // Fattore moltiplicativo (esponenziale)
            'max_wait' => 3600, // Attesa massima (1 ora)
            'jitter' => 0.2 // Jitter per evitare thundering herd
        ]
    ];
    
    /**
     * Costruttore
     * 
     * @param string $storageDir La directory per i file di rate limit
     * @param array $options Opzioni di configurazione
     */
    public function __construct($storageDir = null, array $options = []) {
        // Imposta la directory di storage
        $this->storageDir = $storageDir ?: __DIR__ . '/../cache/ratelimit';
        
        // Crea la directory se non esiste
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
        
        // Imposta le opzioni
        if (isset($options['granularity'])) {
            $this->granularity = max(1, (int)$options['granularity']);
        }
        
        if (isset($options['enable_logging'])) {
            $this->enableLogging = (bool)$options['enable_logging'];
        }
        
        // Carica la configurazione dei servizi
        $this->loadServiceConfig();
        
        // Elimina le registrazioni scadute
        $this->cleanupExpiredRecords();
    }
    
    /**
     * Verifica se un servizio può essere utilizzato (non ha superato il limite)
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa opzionale (per limiti su risorse specifiche)
     * @return bool True se il servizio può essere utilizzato
     */
    public function canUseService($service, $resourceId = 'default') {
        $limits = $this->getServiceLimits($service);
        
        if (!$limits) {
            // Se non ci sono limiti definiti, consenti sempre
            return true;
        }
        
        // Controlla se il servizio è in fase di raffreddamento (backoff)
        $backoffInfo = $this->getBackoffInfo($service, $resourceId);
        if ($backoffInfo && time() < $backoffInfo['until']) {
            // Ancora in fase di raffreddamento
            return false;
        }
        
        // Controlla i normali limiti di frequenza
        $usageCount = $this->getUsageCount($service, $resourceId);
        $limit = $this->getEffectiveLimit($service, $resourceId);
        
        return $usageCount < $limit;
    }
    
    /**
     * Registra l'utilizzo di un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa opzionale (per limiti su risorse specifiche)
     * @param bool $success Se la richiesta ha avuto successo
     * @param array $metadata Metadata aggiuntivi sulla richiesta
     * @return bool True se l'operazione è riuscita
     */
    public function logUsage($service, $resourceId = 'default', $success = true, array $metadata = []) {
        $limits = $this->getServiceLimits($service);
        
        if (!$limits) {
            // Se non ci sono limiti definiti, non serve registrare
            return true;
        }
        
        $usage = $this->loadUsage($service, $resourceId);
        $now = time();
        
        // Arrotonda il timestamp in base alla granularità
        $timeslot = floor($now / $this->granularity) * $this->granularity;
        
        // Inizializza il timeslot se non esiste
        if (!isset($usage['timeslots'][$timeslot])) {
            $usage['timeslots'][$timeslot] = [
                'count' => 0,
                'success' => 0,
                'failures' => 0
            ];
        }
        
        // Aggiorna le statistiche
        $usage['timeslots'][$timeslot]['count']++;
        if ($success) {
            $usage['timeslots'][$timeslot]['success']++;
        } else {
            $usage['timeslots'][$timeslot]['failures']++;
            
            // Se ci sono troppi errori consecutivi, implementa backoff
            if ($this->shouldBackoff($service, $usage, $resourceId)) {
                $this->applyBackoff($service, $resourceId, $usage);
            }
        }
        
        // Aggiorna le statistiche totali
        $usage['total_count'] = ($usage['total_count'] ?? 0) + 1;
        $usage['last_access'] = $now;
        
        // Aggiorna i metadata se forniti
        if (!empty($metadata)) {
            $usage['metadata'] = array_merge($usage['metadata'] ?? [], $metadata);
        }
        
        // Salva il registro aggiornato
        $result = $this->saveUsage($service, $resourceId, $usage);
        
        // Log dettagliato
        if ($this->enableLogging) {
            $this->logRequest($service, $resourceId, $success, $metadata);
        }
        
        return $result;
    }
    
    /**
     * Ottiene il tempo di attesa necessario per il backoff
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @return int|null Secondi di attesa o null se non è necessario attendere
     */
    public function getBackoffWaitTime($service, $resourceId = 'default') {
        $backoffInfo = $this->getBackoffInfo($service, $resourceId);
        
        if (!$backoffInfo) {
            return null;
        }
        
        $waitTime = $backoffInfo['until'] - time();
        return $waitTime > 0 ? $waitTime : null;
    }
    
    /**
     * Ottiene il conteggio dell'utilizzo di un servizio nel periodo corrente
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa opzionale
     * @return int Il numero di utilizzi nel periodo corrente
     */
    public function getUsageCount($service, $resourceId = 'default') {
        $limits = $this->getServiceLimits($service);
        
        if (!$limits) {
            return 0;
        }
        
        $usage = $this->loadUsage($service, $resourceId);
        $now = time();
        $period = $limits['period'];
        $count = 0;
        
        // Conta solo le registrazioni nel periodo corrente
        foreach ($usage['timeslots'] ?? [] as $timestamp => $data) {
            if ($timestamp >= ($now - $period)) {
                $count += $data['count'];
            }
        }
        
        return $count;
    }
    
    /**
     * Ottiene statistiche di utilizzo per un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa opzionale
     * @return array Statistiche di utilizzo
     */
    public function getUsageStats($service, $resourceId = 'default') {
        $limits = $this->getServiceLimits($service);
        $usage = $this->loadUsage($service, $resourceId);
        $now = time();
        
        // Inizializza le statistiche
        $stats = [
            'service' => $service,
            'resource_id' => $resourceId,
            'current_count' => 0,
            'limit' => $limits ? $limits['limit'] : 'unlimited',
            'period' => $limits ? $limits['period'] : null,
            'percent_used' => 0,
            'success_rate' => 0,
            'total_requests' => $usage['total_count'] ?? 0,
            'last_access' => $usage['last_access'] ?? null,
            'in_backoff' => false,
            'backoff_until' => null
        ];
        
        // Calcola le statistiche attuali se ci sono limiti
        if ($limits) {
            $period = $limits['period'];
            $totalInPeriod = 0;
            $successInPeriod = 0;
            
            foreach ($usage['timeslots'] ?? [] as $timestamp => $data) {
                if ($timestamp >= ($now - $period)) {
                    $totalInPeriod += $data['count'];
                    $successInPeriod += $data['success'];
                }
            }
            
            $stats['current_count'] = $totalInPeriod;
            $stats['percent_used'] = $totalInPeriod > 0 ? min(100, ($totalInPeriod / $limits['limit']) * 100) : 0;
            $stats['success_rate'] = $totalInPeriod > 0 ? ($successInPeriod / $totalInPeriod) * 100 : 100;
            
            // Informazioni di backoff
            $backoffInfo = $this->getBackoffInfo($service, $resourceId);
            if ($backoffInfo && $now < $backoffInfo['until']) {
                $stats['in_backoff'] = true;
                $stats['backoff_until'] = $backoffInfo['until'];
                $stats['backoff_wait'] = $backoffInfo['until'] - $now;
                $stats['backoff_attempt'] = $backoffInfo['attempt'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Ottiene i limiti di utilizzo per un servizio
     * 
     * @param string $service Il nome del servizio
     * @return array|null I limiti di utilizzo o null se non definiti
     */
    private function getServiceLimits($service) {
        // Usa la cache se disponibile
        if (isset($this->limitsCache[$service])) {
            return $this->limitsCache[$service];
        }
        
        // Carica la configurazione se non già in cache
        if (empty($this->limitsCache)) {
            $this->loadServiceConfig();
        }
        
        return $this->limitsCache[$service] ?? null;
    }
    
    /**
     * Ottiene il limite effettivo per un servizio, considerando adattamenti dinamici
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @return int Il limite effettivo
     */
    private function getEffectiveLimit($service, $resourceId = 'default') {
        $limits = $this->getServiceLimits($service);
        
        if (!$limits) {
            return PHP_INT_MAX; // Nessun limite
        }
        
        $baseLimit = $limits['limit'];
        
        // Controlla eventuali adattamenti dinamici (implementazione semplificata)
        $usage = $this->loadUsage($service, $resourceId);
        $dynamicLimit = $baseLimit;
        
        // Se il servizio ha avuto errori recenti, riduci il limite temporaneamente
        if (isset($usage['recent_failures']) && $usage['recent_failures'] > 0) {
            $failurePercent = min(80, $usage['recent_failures'] * 10); // Max 80% di riduzione
            $dynamicLimit = (int)max(1, $baseLimit * (1 - ($failurePercent / 100)));
        }
        
        return $dynamicLimit;
    }
    
    /**
     * Carica il registro d'uso per un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @return array Il registro d'uso
     */
    private function loadUsage($service, $resourceId = 'default') {
        $cacheKey = $service . ':' . $resourceId;
        
        // Usa la cache in-memory se disponibile
        if (isset($this->usageCache[$cacheKey])) {
            return $this->usageCache[$cacheKey];
        }
        
        $file = $this->getStorageFilePath($service, $resourceId);
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $usage = unserialize($content) ?: $this->initializeUsage();
        } else {
            $usage = $this->initializeUsage();
        }
        
        // Effettua pulizia registrazioni scadute
        $this->cleanExpiredTimeslots($usage);
        
        // Memorizza nella cache
        $this->usageCache[$cacheKey] = $usage;
        
        return $usage;
    }
    
    /**
     * Inizializza una nuova struttura di utilizzo
     * 
     * @return array Struttura di utilizzo inizializzata
     */
    private function initializeUsage() {
        return [
            'timeslots' => [],
            'total_count' => 0,
            'last_access' => null,
            'recent_failures' => 0,
            'backoff' => null,
            'metadata' => []
        ];
    }
    
    /**
     * Rimuove timeslot obsoleti dalla struttura di utilizzo
     * 
     * @param array &$usage Riferimento alla struttura di utilizzo
     */
    private function cleanExpiredTimeslots(&$usage) {
        if (!isset($usage['timeslots']) || empty($usage['timeslots'])) {
            return;
        }
        
        $now = time();
        $oldestToKeep = $now - (24 * 3600); // Mantieni 24 ore
        
        foreach ($usage['timeslots'] as $timestamp => $data) {
            if ($timestamp < $oldestToKeep) {
                unset($usage['timeslots'][$timestamp]);
            }
        }
        
        // Calcola i recenti fallimenti (ultimi 10 minuti)
        $recentPeriod = $now - (10 * 60);
        $failures = 0;
        $total = 0;
        
        foreach ($usage['timeslots'] as $timestamp => $data) {
            if ($timestamp >= $recentPeriod) {
                $failures += $data['failures'] ?? 0;
                $total += $data['count'] ?? 0;
            }
        }
        
        $usage['recent_failures'] = $total > 0 ? min(10, $failures) : 0;
    }
    
    /**
     * Salva il registro d'uso per un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @param array $usage Il registro d'uso
     * @return bool True se l'operazione è riuscita
     */
    private function saveUsage($service, $resourceId, $usage) {
        $file = $this->getStorageFilePath($service, $resourceId);
        
        // Aggiorna la cache
        $cacheKey = $service . ':' . $resourceId;
        $this->usageCache[$cacheKey] = $usage;
        
        // Salva su file con locking
        $result = $this->writeSafely($file, serialize($usage));
        
        return $result !== false;
    }
    
    /**
     * Ottiene il percorso del file di storage per un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @return string Il percorso del file di storage
     */
    private function getStorageFilePath($service, $resourceId = 'default') {
        $safeService = preg_replace('/[^a-zA-Z0-9_]/', '_', $service);
        $safeResource = preg_replace('/[^a-zA-Z0-9_]/', '_', $resourceId);
        
        return $this->storageDir . '/' . $safeService . '_' . $safeResource . '.ratelimit';
    }
    
    /**
     * Resetta i contatori di utilizzo per un servizio
     * 
     * @param string $service Il nome del servizio o null per tutti
     * @param string $resourceId ID della risorsa opzionale
     * @return bool True se l'operazione è riuscita
     */
    public function reset($service = null, $resourceId = null) {
        // Resetta la cache in-memory
        if ($service !== null) {
            if ($resourceId !== null) {
                // Resetta un servizio e risorsa specifici
                $cacheKey = $service . ':' . $resourceId;
                unset($this->usageCache[$cacheKey]);
                
                $file = $this->getStorageFilePath($service, $resourceId);
                if (file_exists($file)) {
                    return unlink($file);
                }
            } else {
                // Resetta tutte le risorse per un servizio
                foreach ($this->usageCache as $key => $value) {
                    if (strpos($key, $service . ':') === 0) {
                        unset($this->usageCache[$key]);
                    }
                }
                
                $pattern = $this->storageDir . '/' . preg_replace('/[^a-zA-Z0-9_]/', '_', $service) . '_*.ratelimit';
                $files = glob($pattern);
                
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        } else {
            // Resetta tutto
            $this->usageCache = [];
            $files = glob($this->storageDir . '/*.ratelimit');
            
            foreach ($files as $file) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Carica la configurazione dei servizi dal file di configurazione
     */
    private function loadServiceConfig() {
        // Carica la configurazione
        $config = @include __DIR__ . '/../config/services.php';
        
        if (!$config || !isset($config['rate_limits'])) {
            $this->limitsCache = [];
            return;
        }
        
        $this->limitsCache = $config['rate_limits'];
        
        // Carica anche le strategie di backoff
        if (isset($config['backoff_strategies'])) {
            $this->backoffStrategies = array_merge(
                $this->backoffStrategies,
                $config['backoff_strategies']
            );
        }
    }
    
    /**
     * Determina se è necessario applicare backoff per un servizio
     * 
     * @param string $service Il nome del servizio
     * @param array $usage Dati di utilizzo
     * @param string $resourceId ID della risorsa
     * @return bool True se è necessario applicare backoff
     */
    private function shouldBackoff($service, $usage, $resourceId) {
        // Controlla se ci sono troppi errori recenti
        $now = time();
        $recentPeriod = $now - (5 * 60); // Ultimi 5 minuti
        
        $failures = 0;
        $total = 0;
        
        foreach ($usage['timeslots'] as $timestamp => $data) {
            if ($timestamp >= $recentPeriod) {
                $failures += $data['failures'] ?? 0;
                $total += $data['count'] ?? 0;
            }
        }
        
        // Se ci sono almeno 3 richieste e più del 50% sono fallite
        if ($total >= 3 && ($failures / $total) > 0.5) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Applica backoff a un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @param array &$usage Riferimento ai dati di utilizzo
     */
    private function applyBackoff($service, $resourceId, &$usage) {
        $now = time();
        
        // Ottieni la strategia di backoff
        $strategyName = $this->getServiceLimits($service)['backoff_strategy'] ?? 'default';
        $strategy = $this->backoffStrategies[$strategyName] ?? $this->backoffStrategies['default'];
        
        // Ottieni le informazioni di backoff esistenti
        $current = $usage['backoff'] ?? [
            'attempt' => 0,
            'until' => 0
        ];
        
        // Se il backoff precedente è ancora attivo, estendilo
        if ($current['until'] > $now) {
            $current['attempt']++;
        } else {
            // Altrimenti inizia un nuovo backoff
            $current['attempt'] = 1;
        }
        
        // Calcola il tempo di attesa esponenziale
        $base = $strategy['base'];
        $factor = $strategy['factor'];
        $maxWait = $strategy['max_wait'];
        $jitter = $strategy['jitter'];
        
        $wait = min($maxWait, $base * pow($factor, $current['attempt'] - 1));
        
        // Aggiungi jitter (±20%)
        if ($jitter > 0) {
            $jitterAmount = $wait * $jitter;
            $wait = $wait - $jitterAmount + (mt_rand() / mt_getrandmax() * $jitterAmount * 2);
        }
        
        // Imposta il nuovo tempo di backoff
        $current['until'] = $now + (int)$wait;
        
        // Aggiorna i dati di backoff
        $usage['backoff'] = $current;
        
        // Log del backoff
        if ($this->enableLogging) {
            $logFile = $this->storageDir . '/backoff_log.txt';
            $logEntry = date('Y-m-d H:i:s') . " | $service:$resourceId | " .
                        "Attempt: {$current['attempt']} | " .
                        "Wait: " . (int)$wait . "s | " .
                        "Until: " . date('Y-m-d H:i:s', $current['until']) . "\n";
            
            file_put_contents($logFile, $logEntry, FILE_APPEND);
        }
    }
    
    /**
     * Ottiene le informazioni di backoff per un servizio
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @return array|null Informazioni di backoff o null se non disponibili
     */
    private function getBackoffInfo($service, $resourceId) {
        $usage = $this->loadUsage($service, $resourceId);
        
        if (!isset($usage['backoff']) || empty($usage['backoff'])) {
            return null;
        }
        
        return $usage['backoff'];
    }
    
    /**
     * Effettua una pulizia delle registrazioni scadute
     */
    private function cleanupExpiredRecords() {
        $files = glob($this->storageDir . '/*.ratelimit');
        $now = time();
        $olderThan = $now - (7 * 24 * 3600); // Una settimana
        
        foreach ($files as $file) {
            // Controlla la data di ultima modifica del file
            $mtime = filemtime($file);
            
            if ($mtime && $mtime < $olderThan) {
                unlink($file);
                continue;
            }
            
            // Altrimenti carica il contenuto e verifica l'ultimo accesso
            $content = @file_get_contents($file);
            if ($content) {
                $usage = unserialize($content);
                
                if (isset($usage['last_access']) && $usage['last_access'] < $olderThan) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Registra una richiesta nel log
     * 
     * @param string $service Il nome del servizio
     * @param string $resourceId ID della risorsa
     * @param bool $success Se la richiesta ha avuto successo
     * @param array $metadata Metadata aggiuntivi
     */
    private function logRequest($service, $resourceId, $success, array $metadata) {
        $logFile = $this->storageDir . '/requests_log.txt';
        $now = date('Y-m-d H:i:s');
        $status = $success ? 'SUCCESS' : 'FAILURE';
        
        $logEntry = "$now | $service:$resourceId | $status";
        
        if (!empty($metadata)) {
            $logEntry .= ' | ' . json_encode($metadata);
        }
        
        $logEntry .= "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Scrivi dati su file in modo sicuro con locking esclusivo
     * 
     * @param string $file Percorso file
     * @param string $data Dati da scrivere
     * @return bool True se scrittura riuscita
     */
    private function writeSafely($file, $data) {
        $result = false;
        
        $fp = fopen($file, 'w');
        if ($fp) {
            // Acquisizione lock esclusivo
            if (flock($fp, LOCK_EX)) {
                // Svuota il file e scrivi i nuovi dati
                ftruncate($fp, 0);
                $result = fwrite($fp, $data);
                
                // Rilascia il lock
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        
        return $result !== false;
    }
}