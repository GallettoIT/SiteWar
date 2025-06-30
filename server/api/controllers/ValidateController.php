<?php
/**
 * ValidateController
 * 
 * Controller responsabile della validazione degli URL e della verifica
 * della pertinenza del confronto tra i siti tramite AI.
 * 
 * Implementa:
 * - Validazione formato e disponibilità URL
 * - Verifica della pertinenza del confronto tramite AI
 * - Analisi preliminare dei siti per determinare la fattibilità dell'analisi
 * 
 * Pattern utilizzati:
 * - Command Pattern
 * - Facade Pattern
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/ServiceFactory.php';
require_once __DIR__ . '/../../utils/Security.php';
require_once __DIR__ . '/../../utils/Cache.php';

class ValidateController implements Controller {
    /**
     * @var ServiceFactory Factory per la creazione di servizi
     */
    private $serviceFactory;
    
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->serviceFactory = new ServiceFactory();
        $this->cache = new Cache();
    }
    
    /**
     * Gestisce una richiesta HTTP
     * 
     * @param string $method Il metodo HTTP (GET, POST, etc.)
     * @param array $params I parametri della richiesta
     * @return array La risposta da restituire al client
     * @throws Exception Se la richiesta non è valida o si verifica un errore
     */
    public function handleRequest($method, $params) {
        // Verifica il metodo HTTP
        if ($method !== 'POST') {
            throw new Exception('Metodo non supportato. Utilizzare POST.', 405);
        }
        
        // Verifica che siano stati forniti gli URL (supporta sia url1/url2 che site1/site2)
        $url1 = $params['url1'] ?? $params['site1'] ?? null;
        $url2 = $params['url2'] ?? $params['site2'] ?? null;
        
        if (empty($url1) || empty($url2)) {
            throw new Exception('Parametri mancanti. È necessario fornire url1/site1 e url2/site2.', 400);
        }
        
        // Sanitizza e valida gli URL
        $url1 = filter_var($url1, FILTER_SANITIZE_URL);
        $url2 = filter_var($url2, FILTER_SANITIZE_URL);
        
        if (!filter_var($url1, FILTER_VALIDATE_URL) || !filter_var($url2, FILTER_VALIDATE_URL)) {
            throw new Exception('URL non validi. Fornire URL completi (es. https://esempio.com).', 400);
        }
        
        // Verifica se i risultati della validazione sono già in cache
        $cacheKey = md5("validate:{$url1}|{$url2}");
        $cachedResults = $this->cache->get($cacheKey);
        
        if ($cachedResults) {
            return $cachedResults;
        }
        
        // Array per memorizzare i risultati della validazione
        $validationResults = [
            'url1' => [
                'url' => $url1,
                'valid' => false,
                'reachable' => false,
                'statusCode' => null,
                'contentType' => null,
                'error' => null
            ],
            'url2' => [
                'url' => $url2,
                'valid' => false,
                'reachable' => false,
                'statusCode' => null,
                'contentType' => null,
                'error' => null
            ],
            'comparison' => [
                'relevant' => false,
                'relevanceScore' => 0,
                'categories' => [],
                'message' => ''
            ]
        ];
        
        // Verifica la raggiungibilità dei siti
        $validationResults['url1'] = $this->validateUrl($url1);
        $validationResults['url2'] = $this->validateUrl($url2);
        
        // Se entrambi i siti sono raggiungibili, verifica la pertinenza del confronto
        if ($validationResults['url1']['reachable'] && $validationResults['url2']['reachable']) {
            $validationResults['comparison'] = $this->checkRelevance($url1, $url2);
        } else {
            $validationResults['comparison']['message'] = 'Impossibile verificare la pertinenza perché uno o entrambi i siti non sono raggiungibili.';
        }
        
        // Calcola il risultato finale della validazione
        $isValid = $validationResults['url1']['reachable'] && 
                  $validationResults['url2']['reachable'] && 
                  $validationResults['comparison']['relevant'];
        
        $validationResults['valid'] = $isValid;
        
        // Aggiungi un messaggio descrittivo sulla validazione
        if (!$isValid) {
            if (!$validationResults['url1']['reachable']) {
                $validationResults['validation_message'] = "Il primo sito (" . parse_url($url1, PHP_URL_HOST) . ") non è raggiungibile.";
            } elseif (!$validationResults['url2']['reachable']) {
                $validationResults['validation_message'] = "Il secondo sito (" . parse_url($url2, PHP_URL_HOST) . ") non è raggiungibile.";
            } elseif (!$validationResults['comparison']['relevant']) {
                $validationResults['validation_message'] = "Il confronto tra questi siti non è rilevante (punteggio: " . 
                    $validationResults['comparison']['relevanceScore'] . "/100). " . 
                    $validationResults['comparison']['message'];
            }
        }
        
        // Memorizza i risultati in cache
        $this->cache->set($cacheKey, $validationResults, 1800); // Cache per 30 minuti
        
        return $validationResults;
    }
    
    /**
     * Valida un URL verificandone la raggiungibilità
     * 
     * @param string $url L'URL da validare
     * @return array Informazioni di validazione dell'URL
     */
    private function validateUrl($url) {
        $result = [
            'url' => $url,
            'valid' => true,
            'reachable' => false,
            'statusCode' => null,
            'contentType' => null,
            'error' => null
        ];
        
        // Inizializza cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Site War Analyzer Bot/1.0');
        
        $response = curl_exec($ch);
        
        if ($response !== false) {
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            
            $result['statusCode'] = $statusCode;
            $result['contentType'] = $contentType;
            
            // Verifica se lo status code indica una risposta positiva (2xx o 3xx)
            if ($statusCode >= 200 && $statusCode < 400) {
                $result['reachable'] = true;
                
                // Verifica che il content type sia accettabile (HTML)
                if (!empty($contentType) && strpos($contentType, 'text/html') === false) {
                    $result['reachable'] = false;
                    $result['error'] = "Tipo di contenuto non supportato: {$contentType}";
                }
            } else {
                $result['error'] = "URL non raggiungibile. Codice di stato: {$statusCode}";
            }
        } else {
            $result['error'] = 'Errore di connessione: ' . curl_error($ch);
        }
        
        curl_close($ch);
        
        return $result;
    }
    
    /**
     * Verifica la pertinenza del confronto tra due siti utilizzando AI
     * 
     * @param string $url1 Il primo URL
     * @param string $url2 Il secondo URL
     * @return array Informazioni sulla pertinenza del confronto
     */
    private function checkRelevance($url1, $url2) {
        $result = [
            'relevant' => false,
            'relevanceScore' => 0,
            'categories' => [],
            'message' => ''
        ];
        
        try {
            // Debug - log dell'operazione
            if (DEBUG_MODE) {
                error_log("Verifica pertinenza: {$url1} vs {$url2}");
            }
            
            // Utilizza il servizio AI per verificare la pertinenza
            $aiService = $this->serviceFactory->createService('ai', [
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 150
            ]);
            
            // Estrai i domini dagli URL
            $domain1 = parse_url($url1, PHP_URL_HOST);
            $domain2 = parse_url($url2, PHP_URL_HOST);
            
            // Prepara il prompt per l'AI
            $aiService->setPrompt("Analizza la pertinenza di un confronto tecnico tra i siti {$domain1} e {$domain2}.

1. Assegna un punteggio da 0 a 100 che rappresenti quanto sia rilevante confrontare questi due siti (dove 0 = nessuna rilevanza, 100 = massima rilevanza).

2. Identifica da 2 a 5 categorie specifiche che accomunano i due siti (es. e-commerce, blog personali, portali informativi, siti aziendali, portfolio professionali, ecc.).

3. Valuta similarità e differenze in termini di:
   - Target audience
   - Settore di attività
   - Scopo principale del sito
   - Tipo di contenuto

4. Formula una breve spiegazione (max 2 frasi) di quanto sia significativo confrontare questi due siti.

5. Suggerisci aspetti specifici che potrebbero essere interessanti da confrontare (performance, SEO, sicurezza, tecnologie).

Rispondi in formato JSON con questa struttura esatta:
{
  \"score\": [punteggio numerico da 0 a 100],
  \"categories\": [array di stringhe con le categorie identificate],
  \"audience\": [stringa che descrive il target],
  \"explanation\": [spiegazione breve della rilevanza],
  \"focus_areas\": [array di aspetti specifici su cui concentrare il confronto]
}");
            
            // Esegui l'analisi AI
            $success = $aiService->execute();
            
            // Log per debugging
            error_log("[VALIDATION] AI execution success: " . ($success ? 'true' : 'false'));
            if ($aiService->hasError()) {
                error_log("[VALIDATION] AI error: " . $aiService->getErrorMessage());
            }
            
            if (!$aiService->hasError()) {
                $aiResponse = $aiService->getResult();
                error_log("[VALIDATION] AI full response: " . $aiResponse);
                
                // Decodifica la risposta JSON
                $parsedResponse = json_decode($aiResponse, true);
                
                // Verifica la decodifica JSON
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("[VALIDATION] JSON parsing error: " . json_last_error_msg());
                } else {
                    error_log("[VALIDATION] Parsed AI response: " . print_r($parsedResponse, true));
                }
                
                if (is_array($parsedResponse) && isset($parsedResponse['score'])) {
                    $result['relevanceScore'] = intval($parsedResponse['score']);
                    $result['relevant'] = $result['relevanceScore'] >= 50;
                    
                    if (isset($parsedResponse['categories']) && is_array($parsedResponse['categories'])) {
                        $result['categories'] = $parsedResponse['categories'];
                    }
                    
                    if (isset($parsedResponse['explanation'])) {
                        $result['message'] = $parsedResponse['explanation'];
                    }
                    
                    // Aggiungi i nuovi campi avanzati se disponibili
                    if (isset($parsedResponse['audience'])) {
                        $result['audience'] = $parsedResponse['audience'];
                    }
                    
                    if (isset($parsedResponse['focus_areas']) && is_array($parsedResponse['focus_areas'])) {
                        $result['focus_areas'] = $parsedResponse['focus_areas'];
                    }
                } else {
                    $result['message'] = 'Impossibile analizzare la risposta AI. Procedendo con un confronto generico.';
                    $result['relevant'] = true;
                    $result['relevanceScore'] = 60;
                }
            } else {
                // Fallback in caso di errore del servizio AI
                $result['message'] = 'Sistema AI non disponibile. Procedendo con un confronto generico.';
                $result['relevant'] = true;
                $result['relevanceScore'] = 60;
            }
        } catch (Exception $e) {
            // Fallback in caso di eccezione
            $result['message'] = 'Errore durante la verifica della pertinenza. Procedendo con un confronto generico.';
            $result['relevant'] = true;
            $result['relevanceScore'] = 60;
        }
        
        return $result;
    }
}