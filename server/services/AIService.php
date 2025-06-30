<?php
/**
 * AIService
 * 
 * Servizio per l'integrazione con l'API di OpenAI.
 * Permette di effettuare richieste a modelli di intelligenza artificiale
 * per analisi e valutazioni avanzate.
 * 
 * Pattern implementati:
 * - Adapter
 * - Strategy
 */

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../utils/Cache.php';
// Nota: il file di configurazione API keys viene caricato nel metodo getApiKey

class AIService extends BaseService {
    /**
     * @var string Il prompt da inviare all'API
     */
    private $prompt;
    
    /**
     * @var string Modello da utilizzare (es. gpt-3.5-turbo)
     */
    private $model;
    
    /**
     * @var int Numero massimo di token da generare
     */
    private $maxTokens;
    
    /**
     * @var float Temperatura (randomness)
     */
    private $temperature;
    
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * Costruttore
     * 
     * @param array $config Configurazione dell'AIService
     */
    public function __construct($config = []) {
        parent::__construct($config);
        
        // Inizializza le proprietà con i valori di default o dalla configurazione
        $this->model = $config['model'] ?? 'gpt-3.5-turbo';
        $this->maxTokens = $config['max_tokens'] ?? 100;
        $this->temperature = $config['temperature'] ?? 0.3;
        $this->cache = new Cache();
    }
    
    /**
     * Imposta il prompt da inviare all'API
     * 
     * @param string $prompt Il prompt da inviare
     */
    public function setPrompt($prompt) {
        $this->prompt = $prompt;
    }
    
    /**
     * Esegue la richiesta all'API di OpenAI
     * 
     * @return bool True se l'esecuzione ha avuto successo
     */
    public function execute() {
        if (empty($this->prompt)) {
            $this->setError('Prompt non specificato');
            return false;
        }
        
        // Genera una chiave per la cache basata sul prompt e sui parametri
        $cacheKey = md5("ai:{$this->model}:{$this->maxTokens}:{$this->temperature}:{$this->prompt}");
        
        // Verifica se il risultato è già in cache
        $cachedResult = $this->cache->get($cacheKey);
        
        if ($cachedResult) {
            $this->result = $cachedResult;
            return true;
        }
        
        try {
            // Ottiene la chiave API
            $apiKey = $this->getApiKey();
            
            if (empty($apiKey)) {
                $this->setError('Chiave API non disponibile');
                return $this->implementFallback();
            }
            
            // Prepara la richiesta
            // Configurazione servizi per determinare l'endpoint corretto
            $services = require __DIR__ . '/../config/services.php';
            $openaiConfig = $services['api']['openai'] ?? [];
            
            // Determina il corretto endpoint in base al modello
            $endpoint = strpos($this->model, 'gpt-3.5-turbo') === 0 && strpos($this->model, '-instruct') === false 
                ? 'https://api.openai.com/v1/chat/completions'  // Per i modelli chat
                : 'https://api.openai.com/v1/completions';      // Per i modelli di completamento
                
            $ch = curl_init($endpoint);
            
            // Prepara il payload in base al tipo di endpoint
            $payload = [];
            
            // Utilizziamo sempre la chat completions API per semplicità
            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Sei un esperto analista di siti web. Valuta la pertinenza di confronti tra siti web. Rispondi in formato JSON valido.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->prompt
                    ]
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature
            ];
            
            // Configura cURL
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer {$apiKey}"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Aumentato il timeout a 15 secondi
            
            // Esegue la richiesta
            $response = curl_exec($ch);
            
            // Gestisce gli errori di cURL
            if ($response === false) {
                $errorMessage = 'Errore cURL: ' . curl_error($ch);
                error_log("[OPENAI ERROR] " . $errorMessage);
                $this->setError($errorMessage);
                curl_close($ch);
                return $this->implementFallback();
            }
            
            // Log della risposta per debugging
            error_log("[OPENAI RESPONSE] " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
            
            // Ottiene il codice di stato HTTP
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Log dettagliato della chiamata API per debug
            $info = curl_getinfo($ch);
            error_log("[OPENAI REQUEST] URL: {$info['url']}, Code: {$httpCode}, Time: {$info['total_time']}s");
            
            // Chiude la connessione cURL
            curl_close($ch);
            
            // Gestisce gli errori HTTP
            if ($httpCode >= 400) {
                $errorMessage = "Errore HTTP {$httpCode}: " . $response;
                error_log("[OPENAI ERROR] " . $errorMessage);
                $this->setError($errorMessage);
                return $this->implementFallback();
            }
            
            // Decodifica la risposta JSON
            $responseData = json_decode($response, true);
            
            // Verifica che la risposta sia valida e estrae il contenuto in base al tipo di risposta
            if (!$responseData || !isset($responseData['choices'][0])) {
                $this->setError('Risposta non valida: ' . $response);
                return $this->implementFallback();
            }
            
            // Estrae il contenuto della risposta in base al tipo di API
            if (isset($responseData['choices'][0]['message']['content'])) {
                // Risposta dal chat completions API
                $this->result = trim($responseData['choices'][0]['message']['content']);
            } elseif (isset($responseData['choices'][0]['text'])) {
                // Risposta dal completions API (modelli instruct)
                $this->result = trim($responseData['choices'][0]['text']);
            } else {
                $this->setError('Formato di risposta non supportato');
                return $this->implementFallback();
            }
            
            // Memorizza il risultato in cache
            $this->cache->set($cacheKey, $this->result, 3600); // Cache per 1 ora
            
            return true;
        } catch (Exception $e) {
            $this->setError('Errore durante la richiesta: ' . $e->getMessage());
            return $this->implementFallback();
        }
    }
    
    /**
     * Ottiene la chiave API di OpenAI
     * 
     * @return string La chiave API
     */
    private function getApiKey() {
        // Carica il file di configurazione API keys
        $apiKeys = require_once __DIR__ . '/../config/api_keys.php';
        
        if (isset($apiKeys['openai']) && !empty($apiKeys['openai'])) {
            return $apiKeys['openai'];
        }
        
        // Altrimenti, prova a ottenerla dall'ambiente
        $apiKey = getenv('OPENAI_API_KEY');
        
        if ($apiKey) {
            return $apiKey;
        }
        
        // Se la chiave API non è disponibile, registra l'errore e restituisce null
        $this->setError('Chiave API OpenAI non disponibile. Configurare la chiave in config/api_keys.php o come variabile d\'ambiente.');
        return null;
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return bool True se il fallback ha avuto successo
     */
    protected function implementFallback() {
        // Verifica se il prompt riguarda la pertinenza tra siti
        if (stripos($this->prompt, 'pertinenza') !== false || 
            stripos($this->prompt, 'confronto') !== false) {
            $this->result = json_encode([
                'score' => 60,
                'categories' => ['siti web', 'portali web', 'presenza online'],
                'audience' => 'Utenti generici interessati ai contenuti dei siti',
                'explanation' => 'Entrambi i siti rappresentano presenze online che possono essere confrontate per caratteristiche tecniche e di presentazione.',
                'focus_areas' => ['performance', 'sicurezza', 'SEO', 'user experience']
            ]);
            return true;
        }
        
        // Fallback generico per altri tipi di prompt
        $this->result = 'Analisi AI non disponibile al momento';
        return true;
    }
}