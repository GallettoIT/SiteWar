<?php
/**
 * BaseAnalyzer
 * 
 * Classe base astratta per tutti gli analizzatori specifici.
 * Definisce l'interfaccia comune e implementa funzionalità condivise.
 * 
 * Pattern implementati:
 * - Template Method
 * - Strategy
 */

abstract class BaseAnalyzer {
    /**
     * @var string URL del sito da analizzare
     */
    protected $url;
    
    /**
     * @var array Configurazione dell'analizzatore
     */
    protected $config;
    
    /**
     * @var array Risultati dell'analisi
     */
    protected $results = [];
    
    /**
     * @var string Messaggio di errore, se presente
     */
    protected $errorMessage;
    
    /**
     * @var resource Risorsa cURL
     */
    protected $curlHandle;
    
    /**
     * @var string Contenuto HTML della pagina
     */
    protected $pageContent;
    
    /**
     * @var DOMDocument Oggetto DOM della pagina
     */
    protected $dom;
    
    /**
     * @var int Timestamp di inizio dell'analisi
     */
    protected $startTime;
    
    /**
     * @var int Timeout in secondi (aumentato per migliorare il completamento dell'analisi)
     */
    protected $timeout = 45;
    
    /**
     * @var array Intestazioni HTTP della risposta
     */
    protected $headers = [];
    
    /**
     * Costruttore
     * 
     * @param string $url URL del sito da analizzare
     * @param array $config Configurazione opzionale
     */
    public function __construct($url, $config = []) {
        $this->url = $url;
        $this->config = $config;
        
        // Configura il timeout se specificato
        if (isset($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
    }
    
    /**
     * Esegue l'analisi completa
     * 
     * @return bool True se l'analisi è stata completata con successo
     */
    public function analyze() {
        $this->startTime = microtime(true);
        $analyzerName = get_class($this);
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[ANALYZER] {$analyzerName} - Inizio analisi per URL: {$this->url}");
        }
        
        try {
            // Inizializza la richiesta cURL
            $this->initCurl();
            
            // Scarica il contenuto della pagina
            $this->fetchPageContent();
            
            // Inizializza il DOM
            $this->initDom();
            
            // Esegue l'analisi specifica
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYZER] {$analyzerName} - Esecuzione analisi specifica");
            }
            $this->doAnalyze();
            
            // Pulisce le risorse
            $this->cleanup();
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYZER] {$analyzerName} - Analisi completata con successo");
            }
            
            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            $this->cleanup();
            
            error_log("[ANALYZER ERROR] {$analyzerName} - Errore: " . $e->getMessage());
            
            // Tenta di implementare una strategia di fallback
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[ANALYZER] {$analyzerName} - Attivazione fallback");
            }
            return $this->implementFallback();
        }
    }
    
    /**
     * Esegue l'analisi specifica (da implementare nelle classi derivate)
     */
    abstract protected function doAnalyze();
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return bool True se il fallback ha avuto successo
     */
    protected function implementFallback() {
        // Implementazione base: crea un risultato di fallback generico con un punteggio neutro
        $this->results = [
            'totalScore' => 50, // Punteggio neutro
            'status' => 'fallback',
            'error' => $this->errorMessage,
            'fallback' => true
        ];
        return true; // Permette all'analisi complessiva di continuare
    }
    
    /**
     * Ottiene i risultati dell'analisi
     * 
     * @return array Risultati dell'analisi
     */
    public function getResults() {
        // Se la modalità debug è attiva, logghiamo la struttura dei risultati
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $analyzerName = get_class($this);
            error_log("[ANALYZER RESULTS] {$analyzerName} - Risultati: " . print_r(array_keys($this->results), true));
            
            // Verifica se ci sono risultati da API esterne
            if (isset($this->results['external'])) {
                error_log("[ANALYZER API] {$analyzerName} - Contiene dati da API esterne");
            }
        }
        
        return $this->results;
    }
    
    /**
     * Verifica se si è verificato un errore durante l'analisi
     * 
     * @return bool True se si è verificato un errore
     */
    public function hasError() {
        return !empty($this->errorMessage);
    }
    
    /**
     * Ottiene il messaggio di errore, se presente
     * 
     * @return string|null Il messaggio di errore o null se non presente
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    /**
     * Imposta il messaggio di errore
     * 
     * @param string $message Il messaggio di errore
     */
    protected function setError($message) {
        $this->errorMessage = $message;
    }
    
    /**
     * Inizializza la richiesta cURL
     * 
     * @throws Exception Se non è possibile inizializzare cURL
     */
    protected function initCurl() {
        $this->curlHandle = curl_init($this->url);
        
        if (!$this->curlHandle) {
            throw new Exception('Impossibile inizializzare cURL');
        }
        
        // Configura le opzioni cURL
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlHandle, CURLOPT_MAXREDIRS, 5);
        curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT, 15); // Timeout di connessione più breve
        curl_setopt($this->curlHandle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->curlHandle, CURLOPT_DNS_CACHE_TIMEOUT, 600); // Cache DNS più lunga per maggior efficienza
        curl_setopt($this->curlHandle, CURLOPT_USERAGENT, 'Site War Analyzer Bot/1.0');
        curl_setopt($this->curlHandle, CURLOPT_HEADER, true);
    }
    
    /**
     * Scarica il contenuto della pagina
     * 
     * @throws Exception Se non è possibile scaricare il contenuto della pagina
     */
    protected function fetchPageContent() {
        $response = curl_exec($this->curlHandle);
        
        if ($response === false) {
            throw new Exception('Errore durante il download della pagina: ' . curl_error($this->curlHandle));
        }
        
        $httpCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        
        if ($httpCode >= 400) {
            throw new Exception("Errore HTTP {$httpCode} durante il download della pagina");
        }
        
        // Separa header e body
        $headerSize = curl_getinfo($this->curlHandle, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->pageContent = substr($response, $headerSize);
        
        // Memorizza gli header per analisi future
        $this->headers = $this->parseHeaders($header);
    }
    
    /**
     * Inizializza il DOM
     * 
     * @throws Exception Se non è possibile inizializzare il DOM
     */
    protected function initDom() {
        if (empty($this->pageContent)) {
            throw new Exception('Nessun contenuto da analizzare');
        }
        
        // Crea un nuovo DOMDocument
        $this->dom = new DOMDocument();
        
        // Sopprime gli errori di parsing HTML
        $internalErrors = libxml_use_internal_errors(true);
        
        // Carica il contenuto HTML
        $this->dom->loadHTML($this->pageContent, LIBXML_NOWARNING | LIBXML_NOERROR);
        
        // Ripristina la gestione degli errori
        libxml_use_internal_errors($internalErrors);
    }
    
    /**
     * Pulisce le risorse
     */
    protected function cleanup() {
        // Chiude la risorsa cURL
        if ($this->curlHandle) {
            curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }
    
    /**
     * Analizza gli header HTTP
     * 
     * @param string $headerContent Il contenuto degli header
     * @return array Gli header analizzati
     */
    protected function parseHeaders($headerContent) {
        $headers = [];
        
        // Separa gli header per riga
        $headerLines = explode("\r\n", $headerContent);
        
        foreach ($headerLines as $line) {
            // Ignora le righe vuote
            if (empty(trim($line))) {
                continue;
            }
            
            // Controlla se è la prima riga (HTTP/1.1 200 OK)
            if (strpos($line, 'HTTP/') !== false) {
                $headers['status'] = $line;
                continue;
            }
            
            // Divide la riga in nome e valore
            $parts = explode(':', $line, 2);
            
            if (count($parts) == 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                
                // Gestisce header multipli con lo stesso nome
                if (isset($headers[$name])) {
                    if (!is_array($headers[$name])) {
                        $headers[$name] = [$headers[$name]];
                    }
                    $headers[$name][] = $value;
                } else {
                    $headers[$name] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Calcola il tempo trascorso dall'inizio dell'analisi
     * 
     * @return float Tempo trascorso in secondi
     */
    protected function getElapsedTime() {
        return microtime(true) - $this->startTime;
    }
}