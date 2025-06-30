<?php
/**
 * Cache System Advanced
 * 
 * Sistema di cache multi-livello ottimizzato per memorizzare temporaneamente i risultati delle analisi
 * e delle chiamate API. Utilizza file per la persistenza dei dati e memoria per prestazioni migliori.
 */

class Cache {
    /**
     * @var string La directory dove i file di cache sono memorizzati
     */
    private $cacheDir;
    
    /**
     * @var int TTL predefinito in secondi (24 ore)
     */
    private $defaultTtl = 86400;
    
    /**
     * @var array Cache in memoria per prestazioni migliori
     */
    private $memoryCache = [];
    
    /**
     * @var array Conteggio hit/miss per analisi prestazioni
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'memory_hits' => 0,
        'file_hits' => 0
    ];
    
    /**
     * @var int Dimensione massima della cache in memoria (aumentata a 200 items)
     */
    private $memoryCacheSize = 200;
    
    /**
     * @var bool Abilitare la compressione dei dati di cache
     */
    private $useCompression = true;
    
    /**
     * @var array TTL multiplier per categorie diverse per ottimizzazione adattiva
     */
    private $ttlFactors = [
        'performance' => 0.5,  // Cambia più rapidamente
        'seo' => 2.0,          // Cambia più lentamente
        'security' => 1.0,     // Medio
        'technical' => 3.0     // Cambia raramente
    ];
    
    /**
     * Costruttore
     * 
     * @param string $cacheDir La directory per i file di cache
     * @param int $defaultTtl TTL predefinito in secondi
     * @param int $memoryCacheSize Dimensione massima della cache in memoria
     */
    public function __construct($cacheDir = null, $defaultTtl = null, $memoryCacheSize = null) {
        // Carica la configurazione
        $config = require __DIR__ . '/../config/services.php';
        
        // Imposta la directory di cache
        $this->cacheDir = $cacheDir ?: $config['cache']['path'];
        
        // Crea la directory se non esiste
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
            error_log("Creata directory cache: {$this->cacheDir}");
        }
        
        // Assicurati che sia scrivibile
        if (!is_writable($this->cacheDir)) {
            chmod($this->cacheDir, 0755);
            error_log("Modificati permessi directory cache: {$this->cacheDir}");
        }
        
        // Imposta il TTL predefinito
        if ($defaultTtl !== null) {
            $this->defaultTtl = $defaultTtl;
        } elseif (isset($config['cache']['default_ttl'])) {
            $this->defaultTtl = $config['cache']['default_ttl'];
        }
        
        // Imposta la dimensione massima della cache in memoria
        if ($memoryCacheSize !== null) {
            $this->memoryCacheSize = $memoryCacheSize;
        } elseif (isset($config['cache']['memory_cache_size'])) {
            $this->memoryCacheSize = $config['cache']['memory_cache_size'];
        }
        
        // Imposta l'uso della compressione
        if (isset($config['cache']['use_compression'])) {
            $this->useCompression = (bool)$config['cache']['use_compression'];
        }
        
        // Modifica i fattori TTL dalla configurazione se disponibili
        if (isset($config['cache']['ttl_factors']) && is_array($config['cache']['ttl_factors'])) {
            $this->ttlFactors = array_merge($this->ttlFactors, $config['cache']['ttl_factors']);
        }
        
        // Registrazione chiusura per salvare statistiche
        register_shutdown_function([$this, 'saveStats']);
    }
    
    /**
     * Ottiene un elemento dalla cache (con supporto multi-livello)
     * 
     * @param string $key La chiave dell'elemento
     * @return mixed|null Il valore dell'elemento o null se non trovato o scaduto
     */
    public function get($key) {
        // Prova prima la cache in memoria (Livello 1)
        if (isset($this->memoryCache[$key])) {
            $item = $this->memoryCache[$key];
            if (time() <= $item['expires']) {
                $this->stats['hits']++;
                $this->stats['memory_hits']++;
                return $item['data'];
            }
            
            // Se scaduto, rimuovi dalla cache in memoria
            unset($this->memoryCache[$key]);
        }
        
        // Prova la cache su file (Livello 2)
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            $this->stats['misses']++;
            return null;
        }
        
        // Legge il contenuto del file
        $content = file_get_contents($cacheFile);
        
        // Decomprime se necessario
        if ($this->useCompression && $this->isCompressed($content)) {
            $content = $this->decompress($content);
        }
        
        $cacheData = unserialize($content);
        
        // Controlla se il cache è scaduto
        if (time() > $cacheData['expires']) {
            // Elimina il file scaduto e restituisce null
            unlink($cacheFile);
            $this->stats['misses']++;
            return null;
        }
        
        // Aggiungi alla cache in memoria per accessi futuri
        $this->addToMemoryCache($key, $cacheData['data'], $cacheData['expires'] - time());
        
        $this->stats['hits']++;
        $this->stats['file_hits']++;
        
        return $cacheData['data'];
    }
    
    /**
     * Memorizza un elemento nella cache (multi-livello)
     * 
     * @param string $key La chiave dell'elemento
     * @param mixed $value Il valore da memorizzare
     * @param int|null $ttl Tempo di vita in secondi (null per il valore predefinito)
     * @return bool True se l'operazione è riuscita
     */
    public function set($key, $value, $ttl = null) {
        // Calcola il timestamp di scadenza
        $ttl = $ttl !== null ? $ttl : $this->defaultTtl;
        $expires = time() + $ttl;
        
        // Memorizza nella cache in memoria (Livello 1)
        $this->addToMemoryCache($key, $value, $ttl);
        
        // Prepara i dati da memorizzare
        $cacheData = [
            'data' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        // Serializza i dati
        $content = serialize($cacheData);
        
        // Comprimi se abilitato
        if ($this->useCompression) {
            $content = $this->compress($content);
        }
        
        // Scrive nel file di cache con locking
        $cacheFile = $this->getCacheFilePath($key);
        $result = $this->writeSafely($cacheFile, $content);
        
        return $result !== false;
    }
    
    /**
     * Memorizza con strategia di caching adattiva basata sulla categoria
     * 
     * @param string $key Chiave
     * @param mixed $value Valore
     * @param int $ttl TTL base in secondi
     * @param string $category Categoria di dati (performance, seo, security, technical)
     * @return bool Successo
     */
    public function setAdaptive($key, $value, $ttl, $category) {
        // Calcola TTL adattivo
        $factor = $this->ttlFactors[$category] ?? 1.0;
        $adaptiveTtl = (int)($ttl * $factor);
        
        return $this->set($key, $value, $adaptiveTtl);
    }
    
    /**
     * Elimina un elemento dalla cache
     * 
     * @param string $key La chiave dell'elemento
     * @return bool True se l'operazione è riuscita
     */
    public function delete($key) {
        // Rimuovi dalla cache in memoria
        if (isset($this->memoryCache[$key])) {
            unset($this->memoryCache[$key]);
        }
        
        // Rimuovi dalla cache su file
        $cacheFile = $this->getCacheFilePath($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return false;
    }
    
    /**
     * Verifica se un elemento esiste nella cache ed è valido
     * 
     * @param string $key La chiave dell'elemento
     * @return bool True se l'elemento esiste ed è valido
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Elimina tutti gli elementi scaduti dalla cache
     * 
     * @return int Il numero di elementi eliminati
     */
    public function clearExpired() {
        $count = 0;
        $files = glob($this->cacheDir . '/*.cache');
        $now = time();
        
        // Pulisci memoria cache
        foreach ($this->memoryCache as $key => $item) {
            if ($now > $item['expires']) {
                unset($this->memoryCache[$key]);
                $count++;
            }
        }
        
        // Pulisci file cache
        foreach ($files as $file) {
            // Legge il contenuto del file
            $content = file_get_contents($file);
            
            // Decomprime se necessario
            if ($this->useCompression && $this->isCompressed($content)) {
                $content = $this->decompress($content);
            }
            
            $cacheData = unserialize($content);
            
            // Elimina il file se è scaduto
            if ($now > $cacheData['expires']) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Elimina tutti gli elementi dalla cache
     * 
     * @return bool True se l'operazione è riuscita
     */
    public function clear() {
        // Svuota la cache in memoria
        $this->memoryCache = [];
        
        // Elimina i file di cache
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Implementa cache prefetch per analisi frequenti
     * 
     * @param array $patterns Array di pattern di URL da prefetchare
     * @return int Numero di elementi precaricati
     */
    public function prefetchPopularSites($patterns) {
        $files = glob($this->cacheDir . '/*.cache');
        $preloaded = 0;
        
        foreach ($files as $file) {
            if (count($this->memoryCache) >= $this->memoryCacheSize) {
                break; // Evita di sovraccaricare la memoria
            }
            
            $content = file_get_contents($file);
            
            // Decomprime se necessario
            if ($this->useCompression && $this->isCompressed($content)) {
                $content = $this->decompress($content);
            }
            
            $cacheData = unserialize($content);
            
            // Verifica validità
            if (time() > $cacheData['expires']) {
                continue;
            }
            
            // Verifica se il contenuto della cache corrisponde ai pattern
            if (isset($cacheData['data']['site1']['url']) && isset($cacheData['data']['site2']['url'])) {
                $url1 = $cacheData['data']['site1']['url'];
                $url2 = $cacheData['data']['site2']['url'];
                
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $url1) || preg_match($pattern, $url2)) {
                        // Estrai la chiave originale dal nome del file
                        $filename = basename($file, '.cache');
                        $parts = explode('_', $filename);
                        $key = $parts[0];
                        
                        // Carica preventivamente nella cache in memoria
                        $this->memoryCache[$key] = [
                            'data' => $cacheData['data'],
                            'expires' => $cacheData['expires']
                        ];
                        $preloaded++;
                        break;
                    }
                }
            }
        }
        
        return $preloaded;
    }
    
    /**
     * Elimina una percentuale di elementi meno utilizzati quando la cache è troppo grande
     * 
     * @param int $percent Percentuale da eliminare (default 25%)
     * @return int Numero di elementi eliminati
     */
    public function pruneCache($percent = 25) {
        $files = glob($this->cacheDir . '/*.cache');
        $totalFiles = count($files);
        
        if ($totalFiles < 100) {
            return 0; // Non fare nulla se ci sono pochi file
        }
        
        // Determina quanti file eliminare
        $toRemove = (int)ceil($totalFiles * ($percent / 100));
        
        // Ottieni le informazioni sui file
        $fileInfo = [];
        foreach ($files as $file) {
            $fileInfo[] = [
                'path' => $file,
                'accessed' => fileatime($file),
                'size' => filesize($file)
            ];
        }
        
        // Ordina per data di accesso (meno recente prima)
        usort($fileInfo, function($a, $b) {
            return $a['accessed'] - $b['accessed'];
        });
        
        // Prendi i primi $toRemove elementi
        $filesToRemove = array_slice($fileInfo, 0, $toRemove);
        
        // Elimina i file
        $removed = 0;
        foreach ($filesToRemove as $file) {
            if (unlink($file['path'])) {
                $removed++;
            }
        }
        
        return $removed;
    }
    
    /**
     * Ottiene statistiche sulla cache
     * 
     * @return array Statistiche di utilizzo
     */
    public function getStats() {
        $memorySize = 0;
        foreach ($this->memoryCache as $item) {
            $memorySize += strlen(serialize($item['data']));
        }
        
        $fileSize = 0;
        $fileCount = 0;
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            $fileSize += filesize($file);
            $fileCount++;
        }
        
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'memory_hits' => $this->stats['memory_hits'],
            'file_hits' => $this->stats['file_hits'],
            'memory_items' => count($this->memoryCache),
            'file_items' => $fileCount,
            'memory_size' => $memorySize,
            'file_size' => $fileSize,
            'hit_ratio' => $this->stats['hits'] + $this->stats['misses'] > 0 ? 
                round(($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100, 2) : 0
        ];
    }
    
    /**
     * Salva le statistiche della cache in un file di log
     */
    public function saveStats() {
        $statsFile = $this->cacheDir . '/cache_stats.json';
        $stats = $this->getStats();
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    /**
     * Blocco esclusivo per scrittura file sicura
     * 
     * @param string $filePath Percorso file
     * @param string $content Contenuto da scrivere
     * @return bool|int Risultato della scrittura
     */
    private function writeSafely($filePath, $content) {
        $result = false;
        
        $fp = fopen($filePath, 'w');
        if ($fp) {
            // Acquisisci un lock esclusivo
            if (flock($fp, LOCK_EX)) {
                // Svuota il file e scrivi il nuovo contenuto
                ftruncate($fp, 0);
                $result = fwrite($fp, $content);
                
                // Rilascia il lock
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        
        return $result;
    }
    
    /**
     * Aggiunge un elemento alla cache in memoria, gestendo la dimensione massima
     * 
     * @param string $key Chiave
     * @param mixed $value Valore
     * @param int $ttl TTL in secondi
     */
    private function addToMemoryCache($key, $value, $ttl) {
        // Se la cache in memoria è piena, rimuovi l'elemento meno recente
        if (count($this->memoryCache) >= $this->memoryCacheSize && !isset($this->memoryCache[$key])) {
            // Ordina per data di scadenza (prima quelli che scadono prima)
            uasort($this->memoryCache, function($a, $b) {
                return $a['expires'] - $b['expires'];
            });
            
            // Rimuovi il primo elemento (quello che scade prima)
            reset($this->memoryCache);
            unset($this->memoryCache[key($this->memoryCache)]);
        }
        
        // Aggiungi il nuovo elemento
        $this->memoryCache[$key] = [
            'data' => $value,
            'expires' => time() + $ttl
        ];
    }
    
    /**
     * Comprime i dati
     * 
     * @param string $data Dati da comprimere
     * @return string Dati compressi con marker
     */
    private function compress($data) {
        if (function_exists('gzcompress')) {
            $compressed = gzcompress($data, 9);
            return 'COMPRESSED:' . $compressed;
        }
        return $data;
    }
    
    /**
     * Decomprime i dati
     * 
     * @param string $data Dati compressi
     * @return string Dati decompressi
     */
    private function decompress($data) {
        if ($this->isCompressed($data) && function_exists('gzuncompress')) {
            $actualData = substr($data, 11); // Rimuovi il marker 'COMPRESSED:'
            return gzuncompress($actualData);
        }
        return $data;
    }
    
    /**
     * Verifica se i dati sono compressi
     * 
     * @param string $data Dati da verificare
     * @return bool True se i dati sono compressi
     */
    private function isCompressed($data) {
        return strpos($data, 'COMPRESSED:') === 0;
    }
    
    /**
     * Forza il salvataggio di tutti i dati in cache memoria su disco
     * 
     * @return bool True se l'operazione è riuscita
     */
    public function flush() {
        $success = true;
        foreach ($this->memoryCache as $key => $item) {
            $cacheFile = $this->getCacheFilePath($key);
            $cacheData = [
                'data' => $item['data'],
                'expires' => $item['expires'],
                'created' => time()
            ];
            
            // Serializza i dati
            $content = serialize($cacheData);
            
            // Comprimi se abilitato
            if ($this->useCompression) {
                $content = $this->compress($content);
            }
            
            // Scrivi su disco
            if ($this->writeSafely($cacheFile, $content) === false) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Ottiene il percorso del file di cache per una chiave
     * 
     * @param string $key La chiave dell'elemento
     * @return string Il percorso del file di cache
     */
    private function getCacheFilePath($key) {
        // Genera un nome file sicuro
        $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
        $hash = hash('sha256', $key);
        
        return $this->cacheDir . '/' . $safeKey . '_' . $hash . '.cache';
    }
}