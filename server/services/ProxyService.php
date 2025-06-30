<?php
/**
 * Proxy Service
 *
 * Servizio per l'intermediazione con API esterne.
 * Gestisce la comunicazione con API esterne in modo sicuro, nascondendo le chiavi API
 * e implementando strategie di cache e rate limiting.
 *
 * Pattern implementati:
 * - Proxy
 * - Adapter
 */

require_once __DIR__ . '/../utils/Cache.php';
require_once __DIR__ . '/../utils/RateLimiter.php';
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/dto/MozResponseDTO.php';
require_once __DIR__ . '/dto/WhoisResponseDTO.php';

class ProxyService extends BaseService {
    /**
     * @var string Nome del servizio API esterno
     */
    private $serviceName;
    
    /**
     * @var Cache Istanza della cache
     */
    private $cache;
    
    /**
     * @var RateLimiter Istanza del rate limiter
     */
    private $rateLimiter;
    
    /**
     * @var mixed Risposta originale dall'API prima dell'elaborazione
     */
    private $rawResponse;
    
    /**
     * @var bool Flag per abilitare il debug logging
     */
    private $debugMode = true;
    
    /**
     * @var string Directory per i log di debug
     */
    private $debugLogDir;
    
    /**
     * Costruttore
     *
     * @param array $config Configurazione del servizio
     */
    public function __construct($config = []) {
        parent::__construct($config);
        
        // Inizializza il nome del servizio
        $this->serviceName = $config['service'] ?? 'default';
        
        // Inizializza cache e rate limiter
        $this->cache = new Cache();
        $this->rateLimiter = new RateLimiter();
        
        // Imposta la directory per i log di debug
        $this->debugLogDir = __DIR__ . '/../logs/api_debug/';
        $this->ensureDebugLogDirectory();
    }
    
    /**
     * Assicura che la directory di debug esista
     */
    private function ensureDebugLogDirectory() {
        if (!is_dir($this->debugLogDir)) {
            mkdir($this->debugLogDir, 0755, true);
        }
    }
    
    /**
     * Logga i dati di debug in un file strutturato
     *
     * @param string $phase Fase del processo (request, response, parsed)
     * @param mixed $data Dati da loggare
     * @param array $metadata Metadati aggiuntivi
     */
    private function logDebug($phase, $data, $metadata = []) {
        if (!$this->debugMode) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $microtime = microtime(true);
        
        // Crea un nome file univoco per questa sessione
        $sessionId = $metadata['session_id'] ?? uniqid();
        $filename = $this->debugLogDir . $this->serviceName . '_' . date('Y-m-d') . '_' . $sessionId . '.log';
        
        // Prepara il log entry
        $logEntry = [
            'timestamp' => $timestamp,
            'microtime' => $microtime,
            'service' => $this->serviceName,
            'phase' => $phase,
            'metadata' => $metadata,
            'data' => $data
        ];
        
        // Formatta il log in modo leggibile
        $logText = "\n" . str_repeat('=', 80) . "\n";
        $logText .= "[$timestamp] SERVICE: {$this->serviceName} | PHASE: $phase\n";
        $logText .= str_repeat('-', 80) . "\n";
        
        if (!empty($metadata)) {
            $logText .= "METADATA:\n";
            foreach ($metadata as $key => $value) {
                $logText .= "  $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
            $logText .= str_repeat('-', 80) . "\n";
        }
        
        $logText .= "DATA:\n";
        
        // Se i dati sono JSON, formattali in modo leggibile
        if (is_array($data) || is_object($data)) {
            $logText .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        } else {
            $logText .= $data . "\n";
        }
        
        // Scrivi nel file
        file_put_contents($filename, $logText, FILE_APPEND | LOCK_EX);
        
        // Log anche nella error_log standard per debug immediato
        error_log("[PROXY DEBUG] {$this->serviceName} - $phase: " .
                 (is_array($data) ? 'Array with ' . count($data) . ' elements' : substr((string)$data, 0, 200)));
    }
    
    /**
     * Sanitizza gli header per il logging (rimuove token sensibili)
     *
     * @param array $headers
     * @return array
     */
    private function sanitizeHeaders($headers) {
        $sanitized = [];
        $sensitiveHeaders = ['Authorization', 'X-Api-Key', 'Api-Key'];
        
        foreach ($headers as $name => $value) {
            if (in_array($name, $sensitiveHeaders)) {
                $sanitized[$name] = '***REDACTED***';
            } else {
                $sanitized[$name] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Esegue la richiesta all'API esterna
     *
     * @return mixed Il risultato della richiesta
     */
    public function execute() {
        $sessionId = uniqid('api_call_');
        
        // Log della configurazione iniziale
        $this->logDebug('init', [
            'config' => $this->config,
            'service' => $this->serviceName
        ], ['session_id' => $sessionId]);
        
        // Genera la chiave di cache
        $cacheKey = $this->generateCacheKey();
        
        // Controlla se il risultato è in cache
        if ($this->config['use_cache'] ?? true) {
            $cachedResult = $this->cache->get($cacheKey);
            if ($cachedResult !== null) {
                $this->logDebug('cache_hit', $cachedResult, [
                    'session_id' => $sessionId,
                    'cache_key' => $cacheKey
                ]);
                $this->result = $cachedResult;
                return $this->result;
            }
        }
        
        // Controlla il rate limiting
        if (!$this->rateLimiter->canUseService($this->serviceName)) {
            $errorMsg = "Rate limit exceeded for service: {$this->serviceName}. Retry after some time.";
            $this->logDebug('rate_limit_exceeded', ['error' => $errorMsg], ['session_id' => $sessionId]);
            $this->setError($errorMsg);
            error_log("API Rate limit exceeded for service: {$this->serviceName}");
            return $this->implementFallback();
        }
        
        // Prepara la richiesta
        $apiConfig = $this->getServiceConfig();
        $url = $this->buildRequestUrl($apiConfig);
        $headers = $this->getRequestHeaders($apiConfig);
        $data = $this->config['data'] ?? null;
        $method = $this->config['method'] ?? 'GET';
        
        // Log della richiesta
        $this->logDebug('request', [
            'url' => $url,
            'method' => $method,
            'headers' => $this->sanitizeHeaders($headers),
            'data' => $data
        ], ['session_id' => $sessionId]);
        
        error_log("[PROXY] Esecuzione richiesta {$method} a {$url} per servizio: {$this->serviceName}");
        
        // Esegue la richiesta HTTP
        try {
            $response = $this->httpRequest($url, $method, $headers, $data, $sessionId);
            
            // Log della risposta raw
            $this->logDebug('raw_response', $response, [
                'session_id' => $sessionId,
                'response_type' => gettype($response),
                'is_json' => is_array($response)
            ]);
            
            // Registra l'utilizzo dell'API
            $this->rateLimiter->logUsage($this->serviceName);
            
            error_log("[PROXY] Richiesta completata con successo per {$this->serviceName}");
            
            // Elabora la risposta
            $this->result = $this->processResponse($response, $sessionId);
            
            // Log del risultato elaborato
            $this->logDebug('processed_response', $this->result, [
                'session_id' => $sessionId,
                'processing_method' => 'process' . ucfirst($this->serviceName) . 'Response'
            ]);
            
            // Memorizza il risultato in cache se richiesto
            if ($this->config['use_cache'] ?? true) {
                $ttl = $apiConfig['cache_ttl'] ?? null;
                $this->cache->set($cacheKey, $this->result, $ttl);
                $this->logDebug('cache_set', [
                    'cache_key' => $cacheKey,
                    'ttl' => $ttl
                ], ['session_id' => $sessionId]);
                error_log("[PROXY] Risultato memorizzato in cache per {$this->serviceName}");
            }
            
            // Log finale di successo
            $this->logDebug('success', [
                'session_id' => $sessionId,
                'execution_complete' => true
            ], ['session_id' => $sessionId]);
            
            return $this->result;
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $this->logDebug('error', [
                'error_message' => $errorMsg,
                'error_trace' => $e->getTraceAsString()
            ], ['session_id' => $sessionId]);
            
            error_log("[PROXY ERROR] {$this->serviceName}: {$errorMsg}");
            $this->setError($errorMsg);
            error_log("[PROXY] Attivazione fallback per {$this->serviceName}");
            return $this->implementFallback();
        }
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     *
     * @return mixed Il risultato della strategia di fallback
     */
    protected function implementFallback() {
        // Implementazione specifica per ogni tipo di servizio
        switch ($this->serviceName) {
            case 'pagespeed':
                return [
                    'performance_score' => 0,
                    'first_contentful_paint' => null,
                    'largest_contentful_paint' => null,
                    'time_to_interactive' => null,
                    'cumulative_layout_shift' => null,
                    'error' => $this->getErrorMessage()
                ];
                
            case 'moz':
                return [
                    'domain_authority' => null,
                    'page_authority' => null,
                    'backlinks' => 0,
                    'error' => $this->getErrorMessage()
                ];
                
            case 'securityheaders':
                return [
                    'grade' => 'F',
                    'headers' => [],
                    'error' => $this->getErrorMessage()
                ];
                
            case 'whois':
                return [
                    'creation_date' => null,
                    'expiration_date' => null,
                    'registrar' => null,
                    'error' => $this->getErrorMessage()
                ];
                
            case 'w3c_html':
            case 'w3c_css':
                return [
                    'valid' => false,
                    'errors' => [],
                    'warnings' => [],
                    'error' => $this->getErrorMessage()
                ];
                
            default:
                return [
                    'status' => 'error',
                    'message' => $this->getErrorMessage()
                ];
        }
    }
    
    /**
     * Ottiene la configurazione del servizio
     *
     * @return array La configurazione del servizio
     */
    private function getServiceConfig() {
        // Carica la configurazione generale
        $config = require __DIR__ . '/../config/services.php';
        
        // Ottieni la configurazione specifica del servizio
        return $config['api'][$this->serviceName] ?? [];
    }
    
    /**
     * Ottiene le chiavi API
     *
     * @return array Le chiavi API
     */
    private function getApiKeys() {
        // Carica le chiavi API
        if (file_exists(__DIR__ . '/../config/api_keys.php')) {
            return require __DIR__ . '/../config/api_keys.php';
        }
        
        return [];
    }
    
    /**
     * Costruisce l'URL per la richiesta
     *
     * @param array $apiConfig La configurazione dell'API
     * @return string L'URL completo
     */
    private function buildRequestUrl($apiConfig) {
        $url = $apiConfig['url'] ?? '';
        
        // Aggiungi i parametri di base
        $params = $apiConfig['params'] ?? [];
        
        // Aggiungi i parametri specifici della richiesta
        if (isset($this->config['params'])) {
            $params = array_merge($params, $this->config['params']);
        }
        
        // Aggiungi la chiave API come parametro se necessario
        $apiKeys = $this->getApiKeys();
        if ($this->config['api_key_in_url'] ?? false) {
            $keyName = $this->config['api_key_param'] ?? 'key';
            $params[$keyName] = $apiKeys[$this->serviceName] ?? '';
            error_log("[PROXY] Aggiunta API key a URL per {$this->serviceName}, param: {$keyName}");
        }
        
        // Costruisci query string
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $queryString;
        }
        
        // Log dell'URL (nascondendo la chiave API per sicurezza)
        $logUrl = preg_replace('/([?&])(key|api_key)=([^&]+)/', '$1$2=HIDDEN', $url);
        error_log("[PROXY] URL costruito per {$this->serviceName}: {$logUrl}");
        
        return $url;
    }
    
    /**
     * Ottiene gli header per la richiesta
     *
     * @param array $apiConfig La configurazione dell'API
     * @return array Gli header della richiesta
     */
    private function getRequestHeaders($apiConfig) {
        $headers = $apiConfig['headers'] ?? [];
        
        // Aggiungi gli header specifici della richiesta
        if (isset($this->config['headers'])) {
            $headers = array_merge($headers, $this->config['headers']);
        }
        
        // Ottieni le API keys
        $apiKeys = $this->getApiKeys();
        
        // Gestione specifica per Moz API (v2)
        if ($this->serviceName === 'moz') {
            // Utilizza il token direttamente come valore per Authorization
            $headers['Authorization'] = 'Basic ' . $apiKeys['moz'];
            error_log("[PROXY] Configurato header Authorization Basic per MOZ API");
            return $headers;
        }
        
        // Aggiungi la chiave API come header se necessario
        if ($this->config['api_key_in_header'] ?? false) {
            $keyName = $this->config['api_key_header'] ?? 'X-Api-Key';
            $headers[$keyName] = $apiKeys[$this->serviceName] ?? '';
            error_log("[PROXY] Aggiunto header API key per {$this->serviceName}: {$keyName}");
        }
        
        // Aggiungi Authorization Bearer se necessario
        if ($this->config['use_bearer_auth'] ?? false) {
            $headers['Authorization'] = 'Bearer ' . ($apiKeys[$this->serviceName] ?? '');
            error_log("[PROXY] Configurato header Authorization Bearer per {$this->serviceName}");
        }
        
        return $headers;
    }
    
    /**
     * Esegue una richiesta HTTP
     *
     * @param string $url L'URL della richiesta
     * @param string $method Il metodo HTTP
     * @param array $headers Gli header della richiesta
     * @param mixed $data I dati da inviare
     * @param string $sessionId ID della sessione per il logging
     * @return array La risposta
     * @throws Exception Se si verifica un errore
     */
    private function httpRequest($url, $method = 'GET', $headers = [], $data = null, $sessionId = null) {
        $ch = curl_init();
        
        // Opzioni di base
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Aumentato a 60 secondi per evitare timeout durante analisi complesse
        
        // Imposta il metodo HTTP
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Aggiungi i dati per POST/PUT
        if ($method === 'POST' || $method === 'PUT') {
            if (is_array($data)) {
                // Se è un array, invia come form data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                // Altrimenti invia come JSON o raw data
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        // Imposta gli header
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = "$name: $value";
        }
        
        if (!empty($headerLines)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }
        
        // Esegui la richiesta
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        // Log delle informazioni cURL
        $this->logDebug('curl_info', [
            'http_code' => $httpCode,
            'total_time' => $info['total_time'],
            'size_download' => $info['size_download'],
            'content_type' => $info['content_type'] ?? null,
            'error' => $error ?: null
        ], ['url' => $url, 'session_id' => $sessionId]);
        
        curl_close($ch);
        
        // Gestisci gli errori
        if ($error) {
            throw new Exception("HTTP Request Error: $error");
        }
        
        if ($httpCode >= 400) {
            // Log della risposta di errore
            $this->logDebug('http_error', [
                'http_code' => $httpCode,
                'response' => $response
            ], ['url' => $url, 'session_id' => $sessionId]);
            
            throw new Exception("HTTP Error: $httpCode - Response: $response");
        }
        
        // Log della risposta raw prima del parsing
        $this->logDebug('raw_http_response', $response, [
            'length' => strlen($response),
            'starts_with' => substr($response, 0, 100),
            'session_id' => $sessionId
        ]);
        
        // Decodifica JSON se possibile
        $decodedResponse = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodedResponse;
        }
        
        // Log se non è JSON
        $this->logDebug('non_json_response', [
            'json_error' => json_last_error_msg(),
            'response_preview' => substr($response, 0, 500)
        ], ['session_id' => $sessionId]);
        
        // Altrimenti restituisci la risposta come stringa
        return $response;
    }
    
    /**
     * Elabora la risposta in base al tipo di servizio
     *
     * @param mixed $response La risposta da elaborare
     * @param string $sessionId ID della sessione per il logging
     * @return array I dati elaborati
     */
    private function processResponse($response, $sessionId = null) {
        // Memorizza la risposta originale per scopi di analisi/debug
        $this->rawResponse = $response;
        
        // Log pre-elaborazione
        $this->logDebug('pre_process', [
            'service' => $this->serviceName,
            'response_keys' => is_array($response) ? array_keys($response) : 'not_an_array'
        ], ['session_id' => $sessionId]);
        
        // Implementazione specifica per ogni tipo di servizio
        switch ($this->serviceName) {
            case 'pagespeed':
                return $this->processPageSpeedResponse($response);
                
            case 'moz':
                return $this->processMozResponse($response);
                
            case 'securityheaders':
                return $this->processSecurityHeadersResponse($response);
                
            case 'whois':
                return $this->processWhoisResponse($response);
                
            case 'w3c_html':
            case 'w3c_css':
                return $this->processW3CResponse($response);
                
            default:
                return $response;
        }
    }
    
    /**
     * Ottiene la risposta originale non elaborata
     *
     * @return mixed La risposta originale dall'API
     */
    public function getRawResponse() {
        return $this->rawResponse ?? null;
    }
    
    /**
     * Elabora la risposta di PageSpeed
     *
     * @param array $response La risposta
     * @return array I dati elaborati
     */
    private function processPageSpeedResponse($response) {
        $result = [
            'performance_score' => 0,
            'first_contentful_paint' => null,
            'largest_contentful_paint' => null,
            'time_to_interactive' => null,
            'cumulative_layout_shift' => null,
            'error' => null
        ];
        
        if (isset($response['lighthouseResult'])) {
            $lighthouse = $response['lighthouseResult'];
            
            // Punteggio generale
            $result['performance_score'] = $lighthouse['categories']['performance']['score'] * 100;
            
            // Metriche
            $audits = $lighthouse['audits'];
            
            if (isset($audits['first-contentful-paint'])) {
                $result['first_contentful_paint'] = $audits['first-contentful-paint']['numericValue'];
            }
            
            if (isset($audits['largest-contentful-paint'])) {
                $result['largest_contentful_paint'] = $audits['largest-contentful-paint']['numericValue'];
            }
            
            if (isset($audits['interactive'])) {
                $result['time_to_interactive'] = $audits['interactive']['numericValue'];
            }
            
            if (isset($audits['cumulative-layout-shift'])) {
                $result['cumulative_layout_shift'] = $audits['cumulative-layout-shift']['numericValue'];
            }
        }
        
        return $result;
    }
    
    /**
     * Elabora la risposta di Moz utilizzando il DTO specializzato
     *
     * @param array $response La risposta
     * @return array I dati elaborati
     */
    private function processMozResponse($response) {
        error_log("[MOZ API] Elaborazione risposta Moz API");
        
        // Crea un DTO che analizzerà automaticamente la struttura della risposta
        $dto = new MozResponseDTO($response);
        
        // Se il DTO non è valido, utilizza il fallback
        if (!$dto->isValid()) {
            error_log("[MOZ API] Risposta non valida, dati non trovati");
            return [
                'domain_authority' => null,
                'page_authority' => null,
                'backlinks' => 0,
                'error' => 'Dati non presenti nella risposta API'
            ];
        }
        
        // Log dei valori estratti dal DTO
        error_log("[MOZ API] Dati estratti - DA: " . $dto->getDomainAuthority() .
                " PA: " . $dto->getPageAuthority() .
                " Backlinks: " . $dto->getBacklinks());
        
        // Restituisce i dati nel formato standard atteso dal sistema
        return $dto->toArray();
    }
    
    /**
     * Elabora la risposta di Security Headers
     *
     * @param array $response La risposta
     * @return array I dati elaborati
     */
    private function processSecurityHeadersResponse($response) {
        $result = [
            'grade' => 'F',
            'headers' => [],
            'error' => null
        ];
        
        if (isset($response['grade'])) {
            $result['grade'] = $response['grade'];
            $result['headers'] = $response['headers'] ?? [];
        }
        
        return $result;
    }
    
    /**
     * Elabora la risposta di WHOIS utilizzando il DTO specializzato
     *
     * @param array $response La risposta
     * @return array I dati elaborati
     */
    private function processWhoisResponse($response) {
        error_log("[WHOIS API] Elaborazione risposta WHOIS API");
        
        // Crea un DTO che analizzerà automaticamente la struttura della risposta
        $dto = new WhoisResponseDTO($response);
        
        // Se il DTO non è valido, utilizza il fallback
        if (!$dto->isValid()) {
            error_log("[WHOIS API] Risposta non valida, dati non trovati");
            return [
                'creation_date' => null,
                'expiration_date' => null,
                'registrar' => null,
                'error' => 'Dati non presenti nella risposta API'
            ];
        }
        
        // Log dei valori estratti dal DTO
        error_log("[WHOIS API] Dati estratti - Creazione: " . $dto->getCreationDate() .
                " Scadenza: " . $dto->getExpirationDate() .
                " Registrar: " . $dto->getRegistrar());
        
        // Restituisce i dati nel formato standard atteso dal sistema
        return $dto->toArray();
    }
    
    /**
     * Elabora la risposta di W3C Validator
     *
     * @param mixed $response La risposta
     * @return array I dati elaborati
     */
    private function processW3CResponse($response) {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'error' => null
        ];
        
        if (is_array($response)) {
            // HTML Validator
            if (isset($response['messages'])) {
                $errors = 0;
                $warnings = 0;
                
                foreach ($response['messages'] as $message) {
                    if ($message['type'] === 'error') {
                        $errors++;
                        $result['errors'][] = $message['message'];
                    } elseif ($message['type'] === 'warning') {
                        $warnings++;
                        $result['warnings'][] = $message['message'];
                    }
                }
                
                $result['valid'] = ($errors === 0);
            }
            // CSS Validator
            elseif (isset($response['cssvalidation'])) {
                $validation = $response['cssvalidation'];
                
                $result['valid'] = $validation['valid'] ?? false;
                $result['errors'] = $validation['errors'] ?? [];
                $result['warnings'] = $validation['warnings'] ?? [];
            }
        }
        
        return $result;
    }
    
    /**
     * Genera una chiave di cache per la richiesta corrente
     *
     * @return string La chiave di cache
     */
    private function generateCacheKey() {
        $keyParts = [
            'service' => $this->serviceName,
            'params' => $this->config['params'] ?? [],
            'data' => $this->config['data'] ?? null,
            'method' => $this->config['method'] ?? 'GET'
        ];
        
        return 'proxy_' . md5(serialize($keyParts));
    }
}
