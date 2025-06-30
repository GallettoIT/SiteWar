# Componente Backend - Documentazione Tecnica

## 1. Panoramica

Il componente backend di Site War è responsabile dell'elaborazione delle richieste, dell'orchestrazione delle analisi avanzate, della comunicazione con le API esterne e della generazione dei risultati finali. Implementato in PHP puro senza framework architetturali, questo componente segue un'architettura modulare con pattern di design ben definiti.

## 2. Architettura del Backend

### 2.1 Diagramma delle Classi
```
┌───────────────────┐
│  APIController    │
├───────────────────┤
│ - route()         │
│ - processRequest()│
│ - sendResponse()  │
└─────────┬─────────┘
          │
    ┌─────┴──────┬────────────┬────────────┬────────────┐
    │            │            │            │            │
    ▼            ▼            ▼            ▼            ▼
┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐
│Validator │ │AnalyzerS│ │ProxyServ│ │AIService│ │ResultGen│
├─────────┤ ├─────────┤ ├─────────┤ ├─────────┤ ├─────────┤
│- validate│ │- analyze│ │- request│ │- check  │ │- process│
│- sanitize│ │- fetch  │ │- forward│ │- analyze│ │- compare│
└─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘
                │
                ▼
          ┌─────────────┐
          │ServiceFactory│
          ├─────────────┤
          │- createSEO() │
          │- createSec() │
          │- createPerf()│
          └─────────────┘
```

### 2.2 Moduli Principali

#### 2.2.1 APIController
Gestisce le richieste HTTP in entrata, le indirizza ai servizi appropriati e formatta le risposte.

#### 2.2.2 Validator
Valida e sanitizza gli input dell'utente, verificando la correttezza e la sicurezza dei dati.

#### 2.2.3 AnalyzerService
Coordina le analisi avanzate che richiedono elaborazione lato server.

#### 2.2.4 ProxyService
Gestisce le comunicazioni sicure con API di terze parti, nascondendo le chiavi API e gestendo il rate limiting.

#### 2.2.5 AIService
Utilizza l'intelligenza artificiale per valutare la pertinenza del confronto tra i siti.

#### 2.2.6 ResultGenerator
Elabora i risultati finali, calcola i punteggi e determina il vincitore della battaglia.

#### 2.2.7 ServiceFactory
Crea istanze dei vari servizi di analisi utilizzando il Factory Method Pattern.

## 3. Implementazione API

### 3.1 Struttura dell'API

```
/server/api/
  ├── index.php          # Entry point per tutte le richieste API
  ├── controllers/       # Controller per diversi endpoint
  │   ├── AnalyzeController.php
  │   ├── ValidateController.php
  │   └── ReportController.php
  ├── services/          # Servizi per la business logic
  │   ├── analyzer/      # Servizi di analisi
  │   │   ├── SEOAnalyzer.php
  │   │   ├── SecurityAnalyzer.php
  │   │   ├── PerformanceAnalyzer.php
  │   │   └── TechAnalyzer.php
  │   ├── AIService.php  # Servizio di validazione AI
  │   ├── ProxyService.php # Proxy per API esterne
  │   └── ResultService.php # Generazione risultati
  ├── utils/             # Utility e helper
  │   ├── Validator.php  # Validazione input
  │   ├── HttpClient.php # Client HTTP
  │   ├── Cache.php      # Sistema di cache
  │   └── Security.php   # Funzioni di sicurezza
  └── config/            # Configurazioni
      ├── api_keys.php   # Chiavi API (protette)
      └── services.php   # Configurazione servizi
```

### 3.2 Endpoint API

#### 3.2.1 /api/analyze
**Metodo**: POST  
**Parametri**:
- `site1`: URL del primo sito (obbligatorio)
- `site2`: URL del secondo sito (obbligatorio)

**Descrizione**: Avvia l'analisi completa di entrambi i siti e restituisce i risultati.

#### 3.2.2 /api/validate
**Metodo**: POST  
**Parametri**:
- `site1`: URL del primo sito (obbligatorio)
- `site2`: URL del secondo sito (obbligatorio)

**Descrizione**: Valida gli URL e verifica la pertinenza del confronto utilizzando l'AI.

#### 3.2.3 /api/progress
**Metodo**: GET  
**Parametri**:
- `session_id`: ID della sessione di analisi (obbligatorio)

**Descrizione**: Restituisce lo stato di avanzamento di un'analisi in corso.

### 3.3 Formato della Risposta

Tutte le risposte API utilizzano JSON con la seguente struttura:

```json
{
  "status": "success|error",
  "data": {
    // Dati specifici della risposta
  },
  "message": "Messaggio informativo (opzionale)"
}
```

Per le analisi complete, la struttura dei dati è:

```json
{
  "status": "success",
  "data": {
    "site1": {
      "url": "https://example1.com",
      "performance": {
        "score": 85,
        "metrics": {
          "fcp": 1200,
          "lcp": 2500,
          "tti": 3500,
          "cls": 0.05,
          "totalSize": 1500000
        }
      },
      "seo": {
        "score": 78,
        "metrics": {
          "title": "Good",
          "meta": "Average",
          "headings": "Good",
          "images": "Average",
          "links": "Good"
        }
      },
      "security": {
        "score": 92,
        "metrics": {
          "ssl": "A+",
          "headers": "Good",
          "vulnerabilities": 0,
          "outdated": false
        }
      },
      "technical": {
        "score": 88,
        "metrics": {
          "html": "Valid",
          "css": "Valid",
          "javascript": "Modern",
          "responsive": true,
          "technologies": ["HTML5", "CSS3", "JavaScript", "jQuery"]
        }
      }
    },
    "site2": {
      // Struttura simile a site1
    },
    "winner": "site1",
    "comparison": {
      "performance": "site1",
      "seo": "site2",
      "security": "site1",
      "technical": "tie"
    }
  }
}
```

## 4. Pseudocodice Principale

### 4.1 APIController (index.php)
```php
<?php
// Importare le classi necessarie
require_once 'config/config.php';
require_once 'controllers/AnalyzeController.php';
require_once 'controllers/ValidateController.php';
require_once 'controllers/ReportController.php';
require_once 'utils/Security.php';

// Classe principale per il routing delle richieste API
class APIController {
    // Array di controller disponibili
    private $controllers = [];
    
    // Costruttore
    public function __construct() {
        // Inizializzare i controller
        $this->controllers = [
            'analyze' => new AnalyzeController(),
            'validate' => new ValidateController(),
            'progress' => new ReportController()
        ];
        
        // Impostare headers per CORS e tipo di contenuto
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
    }
    
    // Elaborare la richiesta
    public function processRequest() {
        // Ottenere il percorso dalla richiesta
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', $uri);
        
        // Individuare l'endpoint
        $endpoint = isset($uri[2]) ? $uri[2] : '';
        
        // Verificare se l'endpoint esiste
        if (!array_key_exists($endpoint, $this->controllers)) {
            $this->sendResponse(404, ['status' => 'error', 'message' => 'Endpoint not found']);
            return;
        }
        
        // Verificare il metodo HTTP
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'OPTIONS') {
            $this->sendResponse(200, ['status' => 'success']);
            return;
        }
        
        // Ottenere i parametri della richiesta
        $params = [];
        if ($method === 'POST') {
            $content = file_get_contents('php://input');
            $params = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $params = $_POST; // Fallback su $_POST se il JSON non è valido
            }
        } else if ($method === 'GET') {
            $params = $_GET;
        }
        
        // Sanitizzare i parametri
        $params = Security::sanitizeInput($params);
        
        try {
            // Elaborare la richiesta con il controller appropriato
            $result = $this->controllers[$endpoint]->handleRequest($method, $params);
            $this->sendResponse(200, $result);
        } catch (InvalidArgumentException $e) {
            $this->sendResponse(400, ['status' => 'error', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            $this->sendResponse(500, ['status' => 'error', 'message' => 'Internal server error']);
        }
    }
    
    // Inviare la risposta
    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Istanziare e avviare il controller API
$apiController = new APIController();
$apiController->processRequest();
?>
```

### 4.2 AnalyzeController
```php
<?php
require_once 'services/analyzer/SEOAnalyzer.php';
require_once 'services/analyzer/SecurityAnalyzer.php';
require_once 'services/analyzer/PerformanceAnalyzer.php';
require_once 'services/analyzer/TechAnalyzer.php';
require_once 'services/AIService.php';
require_once 'services/ResultService.php';
require_once 'utils/Validator.php';

class AnalyzeController {
    private $validator;
    private $aiService;
    private $resultService;
    
    public function __construct() {
        $this->validator = new Validator();
        $this->aiService = new AIService();
        $this->resultService = new ResultService();
    }
    
    public function handleRequest($method, $params) {
        // Verificare il metodo HTTP
        if ($method !== 'POST') {
            throw new InvalidArgumentException('Method not allowed');
        }
        
        // Validare i parametri
        if (!isset($params['site1']) || !isset($params['site2'])) {
            throw new InvalidArgumentException('Missing required parameters: site1, site2');
        }
        
        $site1 = $params['site1'];
        $site2 = $params['site2'];
        
        // Validare gli URL
        if (!$this->validator->isValidUrl($site1) || !$this->validator->isValidUrl($site2)) {
            throw new InvalidArgumentException('Invalid URL format');
        }
        
        // Verificare la pertinenza del confronto con l'IA
        $relevanceCheck = $this->aiService->checkRelevance($site1, $site2);
        if (!$relevanceCheck['relevant']) {
            return [
                'status' => 'error',
                'message' => 'I due siti non sono confrontabili',
                'details' => $relevanceCheck['reason']
            ];
        }
        
        // Creare una nuova sessione di analisi
        $sessionId = uniqid('analysis_');
        
        // Avviare le analisi in parallelo
        $site1Results = $this->analyzeSite($site1);
        $site2Results = $this->analyzeSite($site2);
        
        // Elaborare i risultati e determinare il vincitore
        $finalResults = $this->resultService->processResults($site1, $site2, $site1Results, $site2Results);
        
        return [
            'status' => 'success',
            'data' => $finalResults
        ];
    }
    
    private function analyzeSite($url) {
        // Creare gli analizzatori
        $seoAnalyzer = new SEOAnalyzer($url);
        $securityAnalyzer = new SecurityAnalyzer($url);
        $performanceAnalyzer = new PerformanceAnalyzer($url);
        $techAnalyzer = new TechAnalyzer($url);
        
        // Eseguire le analisi
        $seoResults = $seoAnalyzer->analyze();
        $securityResults = $securityAnalyzer->analyze();
        $performanceResults = $performanceAnalyzer->analyze();
        $techResults = $techAnalyzer->analyze();
        
        // Combinare i risultati
        return [
            'url' => $url,
            'seo' => $seoResults,
            'security' => $securityResults,
            'performance' => $performanceResults,
            'technical' => $techResults
        ];
    }
}
?>
```

### 4.3 SecurityAnalyzer
```php
<?php
require_once 'utils/HttpClient.php';
require_once 'utils/Cache.php';
require_once 'config/api_keys.php';

class SecurityAnalyzer {
    private $url;
    private $httpClient;
    private $cache;
    
    public function __construct($url) {
        $this->url = $url;
        $this->httpClient = new HttpClient();
        $this->cache = new Cache();
    }
    
    public function analyze() {
        // Verificare se i risultati sono in cache
        $cacheKey = 'security_' . md5($this->url);
        $cachedResults = $this->cache->get($cacheKey);
        
        if ($cachedResults !== null) {
            return $cachedResults;
        }
        
        // Eseguire l'analisi SSL
        $sslResults = $this->checkSSL();
        
        // Verificare gli header di sicurezza
        $headerResults = $this->checkSecurityHeaders();
        
        // Verificare vulnerabilità note
        $vulnerabilityResults = $this->checkVulnerabilities();
        
        // Calcolare il punteggio
        $score = $this->calculateScore($sslResults, $headerResults, $vulnerabilityResults);
        
        // Preparare i risultati
        $results = [
            'score' => $score,
            'metrics' => [
                'ssl' => $sslResults['grade'],
                'headers' => $this->getHeadersRating($headerResults),
                'vulnerabilities' => count($vulnerabilityResults),
                'outdated' => $this->hasOutdatedSoftware()
            ],
            'details' => [
                'ssl' => $sslResults,
                'headers' => $headerResults,
                'vulnerabilities' => $vulnerabilityResults
            ]
        ];
        
        // Salvare i risultati in cache (24 ore)
        $this->cache->set($cacheKey, $results, 86400);
        
        return $results;
    }
    
    private function checkSSL() {
        try {
            // Utilizzare l'API di Security Headers per verificare SSL
            $apiUrl = 'https://api.security-headers.io/v1/analyze';
            $response = $this->httpClient->get($apiUrl, [
                'q' => $this->url,
                'apiKey' => API_KEYS['security_headers']
            ]);
            
            if (isset($response['ssl_response'])) {
                return [
                    'grade' => $response['ssl_response']['grade'] ?? 'Unknown',
                    'valid' => $response['ssl_response']['is_valid'] ?? false,
                    'expires' => $response['ssl_response']['expires'] ?? null,
                    'issuer' => $response['ssl_response']['issuer'] ?? 'Unknown'
                ];
            }
            
            // Fallback a una valutazione semplice
            return $this->performBasicSSLCheck();
            
        } catch (Exception $e) {
            // Fallback in caso di errore
            return $this->performBasicSSLCheck();
        }
    }
    
    private function performBasicSSLCheck() {
        $urlParts = parse_url($this->url);
        $domain = $urlParts['host'];
        
        // Verificare se il certificato è valido
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'capture_peer_cert' => true
            ]
        ]);
        
        $result = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if ($result) {
            $cert = stream_context_get_params($result);
            $certInfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
            
            return [
                'grade' => 'B', // Valutazione predefinita per SSL valido
                'valid' => true,
                'expires' => date('Y-m-d H:i:s', $certInfo['validTo_time_t']),
                'issuer' => $certInfo['issuer']['CN']
            ];
        }
        
        return [
            'grade' => 'F', // Fail
            'valid' => false,
            'expires' => null,
            'issuer' => 'Unknown'
        ];
    }
    
    private function checkSecurityHeaders() {
        try {
            // Utilizzare l'API di Security Headers
            $apiUrl = 'https://api.security-headers.io/v1/analyze';
            $response = $this->httpClient->get($apiUrl, [
                'q' => $this->url,
                'apiKey' => API_KEYS['security_headers'],
                'hidden' => true
            ]);
            
            if (isset($response['headers'])) {
                return $response['headers'];
            }
            
            // Fallback a una valutazione semplice
            return $this->getBasicHeaders();
            
        } catch (Exception $e) {
            // Fallback in caso di errore
            return $this->getBasicHeaders();
        }
    }
    
    private function getBasicHeaders() {
        $headers = get_headers($this->url, 1);
        $securityHeaders = [];
        
        // Verificare gli header di sicurezza principali
        $importantHeaders = [
            'Strict-Transport-Security',
            'Content-Security-Policy',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Referrer-Policy'
        ];
        
        foreach ($importantHeaders as $header) {
            $securityHeaders[$header] = isset($headers[$header]) ? $headers[$header] : 'missing';
        }
        
        return $securityHeaders;
    }
    
    private function getHeadersRating($headers) {
        // Valutare gli header di sicurezza
        $rating = 'Poor';
        $count = 0;
        
        $importantHeaders = [
            'Strict-Transport-Security',
            'Content-Security-Policy',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Referrer-Policy'
        ];
        
        foreach ($importantHeaders as $header) {
            if (isset($headers[$header]) && $headers[$header] !== 'missing') {
                $count++;
            }
        }
        
        if ($count >= 5) {
            $rating = 'Excellent';
        } else if ($count >= 3) {
            $rating = 'Good';
        } else if ($count >= 1) {
            $rating = 'Average';
        }
        
        return $rating;
    }
    
    private function checkVulnerabilities() {
        // Questa è una versione semplificata, nella realtà richiederebbe
        // l'integrazione con API di sicurezza più avanzate
        return [];
    }
    
    private function hasOutdatedSoftware() {
        // Questa è una versione semplificata
        return false;
    }
    
    private function calculateScore($sslResults, $headerResults, $vulnerabilityResults) {
        // Calcolare un punteggio in base ai risultati
        $score = 0;
        
        // Valutazione SSL (max 40 punti)
        $sslGrades = ['A+' => 40, 'A' => 35, 'B' => 30, 'C' => 20, 'D' => 10, 'F' => 0];
        $sslGrade = $sslResults['grade'];
        $score += isset($sslGrades[$sslGrade]) ? $sslGrades[$sslGrade] : 0;
        
        // Valutazione header (max 40 punti)
        $headerRatings = ['Excellent' => 40, 'Good' => 30, 'Average' => 20, 'Poor' => 10];
        $headerRating = $this->getHeadersRating($headerResults);
        $score += isset($headerRatings[$headerRating]) ? $headerRatings[$headerRating] : 0;
        
        // Vulnerabilità (max 20 punti)
        $vulnerabilityCount = count($vulnerabilityResults);
        if ($vulnerabilityCount === 0) {
            $score += 20;
        } else if ($vulnerabilityCount <= 2) {
            $score += 10;
        }
        
        return $score;
    }
}
?>
```

### 4.4 ResultService
```php
<?php
class ResultService {
    public function processResults($site1Url, $site2Url, $site1Results, $site2Results) {
        // Elaborare i risultati e determinare il vincitore
        $comparison = $this->compareResults($site1Results, $site2Results);
        
        // Calcolare i punteggi finali
        $site1Score = $this->calculateFinalScore($site1Results);
        $site2Score = $this->calculateFinalScore($site2Results);
        
        // Determinare il vincitore
        $winner = ($site1Score > $site2Score) ? 'site1' : 'site2';
        
        // In caso di parità, dare priorità a sicurezza e performance
        if ($site1Score === $site2Score) {
            if ($site1Results['security']['score'] > $site2Results['security']['score']) {
                $winner = 'site1';
            } else if ($site1Results['security']['score'] < $site2Results['security']['score']) {
                $winner = 'site2';
            } else if ($site1Results['performance']['score'] > $site2Results['performance']['score']) {
                $winner = 'site1';
            } else if ($site1Results['performance']['score'] < $site2Results['performance']['score']) {
                $winner = 'site2';
            }
        }
        
        // Compilare il risultato finale
        return [
            'site1' => $site1Results,
            'site2' => $site2Results,
            'winner' => $winner,
            'comparison' => $comparison
        ];
    }
    
    private function compareResults($site1Results, $site2Results) {
        $comparison = [];
        
        // Confrontare le categorie principali
        $categories = ['performance', 'seo', 'security', 'technical'];
        
        foreach ($categories as $category) {
            if ($site1Results[$category]['score'] > $site2Results[$category]['score']) {
                $comparison[$category] = 'site1';
            } else if ($site1Results[$category]['score'] < $site2Results[$category]['score']) {
                $comparison[$category] = 'site2';
            } else {
                $comparison[$category] = 'tie';
            }
        }
        
        return $comparison;
    }
    
    private function calculateFinalScore($siteResults) {
        // Ponderazione delle categorie
        $weights = [
            'performance' => 0.3,
            'seo' => 0.25,
            'security' => 0.25,
            'technical' => 0.2
        ];
        
        $finalScore = 0;
        
        foreach ($weights as $category => $weight) {
            $finalScore += $siteResults[$category]['score'] * $weight;
        }
        
        return round($finalScore, 2);
    }
}
?>
```

## 5. Integrazione con API Esterne

### 5.1 API Utilizzate

| API | Scopo | Endpoint |
|-----|-------|----------|
| Google PageSpeed Insights | Analisi performance | https://www.googleapis.com/pagespeedonline/v5/runPagespeed |
| Moz API | Metriche SEO | https://moz.com/api |
| Security Headers | Analisi sicurezza | https://api.security-headers.io |
| WHOIS API | Informazioni domini | https://www.whoisxmlapi.com/api |
| W3C Validator | Validazione HTML/CSS | https://validator.w3.org/api |
| Wappalyzer API | Rilevamento tecnologie | https://api.wappalyzer.com |
| OpenAI API | Validazione pertinenza | https://api.openai.com/v1 |

### 5.2 Sistema di Proxy

Il sistema di proxy per le API esterne è implementato tramite la classe `ProxyService` che:

1. Nasconde le chiavi API dal frontend
2. Implementa il rate limiting per evitare il superamento delle quote
3. Gestisce la cache per ridurre le chiamate ripetute
4. Gestisce gli errori e implementa strategie di fallback

```php
<?php
class ProxyService {
    private $httpClient;
    private $cache;
    private $rateLimiter;
    
    public function __construct() {
        $this->httpClient = new HttpClient();
        $this->cache = new Cache();
        $this->rateLimiter = new RateLimiter();
    }
    
    public function forwardRequest($service, $endpoint, $params) {
        // Chiave di cache basata su servizio, endpoint e parametri
        $cacheKey = 'proxy_' . $service . '_' . md5(json_encode([$endpoint, $params]));
        
        // Verificare se i risultati sono in cache
        $cachedResponse = $this->cache->get($cacheKey);
        if ($cachedResponse !== null) {
            return $cachedResponse;
        }
        
        // Verificare il rate limiting
        if (!$this->rateLimiter->canMakeRequest($service)) {
            throw new Exception('Rate limit exceeded for ' . $service);
        }
        
        try {
            // Ottenere la configurazione del servizio
            $serviceConfig = $this->getServiceConfig($service);
            
            // Aggiungere la chiave API
            if (isset($serviceConfig['api_key_param'])) {
                $params[$serviceConfig['api_key_param']] = $serviceConfig['api_key'];
            }
            
            // Comporre l'URL completo
            $url = $serviceConfig['base_url'] . $endpoint;
            
            // Eseguire la richiesta
            $method = $serviceConfig['method'] ?? 'GET';
            
            if ($method === 'GET') {
                $response = $this->httpClient->get($url, $params);
            } else {
                $response = $this->httpClient->post($url, $params);
            }
            
            // Salvare in cache
            $cacheDuration = $serviceConfig['cache_duration'] ?? 3600; // Default 1 ora
            $this->cache->set($cacheKey, $response, $cacheDuration);
            
            // Registrare la richiesta per il rate limiting
            $this->rateLimiter->registerRequest($service);
            
            return $response;
            
        } catch (Exception $e) {
            // Registrare l'errore
            error_log('Proxy error for ' . $service . ': ' . $e->getMessage());
            
            // Implementare fallback se disponibile
            return $this->implementFallback($service, $endpoint, $params);
        }
    }
    
    private function getServiceConfig($service) {
        $configs = [
            'pagespeed' => [
                'base_url' => 'https://www.googleapis.com/pagespeedonline/v5/',
                'api_key_param' => 'key',
                'api_key' => API_KEYS['google_pagespeed'],
                'method' => 'GET',
                'cache_duration' => 86400 // 24 ore
            ],
            'moz' => [
                'base_url' => 'https://moz.com/api/',
                'api_key_param' => 'access_id',
                'api_key' => API_KEYS['moz_access_id'],
                'secret_param' => 'secret_key',
                'secret_key' => API_KEYS['moz_secret_key'],
                'method' => 'GET',
                'cache_duration' => 86400 // 24 ore
            ],
            // Altre configurazioni...
        ];
        
        if (!isset($configs[$service])) {
            throw new Exception('Service configuration not found for ' . $service);
        }
        
        return $configs[$service];
    }
    
    private function implementFallback($service, $endpoint, $params) {
        // Implementare strategie di fallback specifiche per servizio
        switch ($service) {
            case 'pagespeed':
                return $this->fallbackPageSpeed($params);
            case 'security_headers':
                return $this->fallbackSecurityHeaders($params);
            // Altri fallback...
            default:
                return [
                    'error' => true,
                    'message' => 'Service temporarily unavailable'
                ];
        }
    }
    
    // Implementazioni di fallback
    private function fallbackPageSpeed($params) {
        // Implementazione semplificata di analisi performance
        // ...
    }
    
    private function fallbackSecurityHeaders($params) {
        // Implementazione semplificata di analisi sicurezza
        // ...
    }
}
?>
```

### 5.3 AIService
```php
<?php
class AIService {
    private $proxyService;
    
    public function __construct() {
        $this->proxyService = new ProxyService();
    }
    
    public function checkRelevance($site1Url, $site2Url) {
        try {
            // Verificare se i risultati sono in cache
            $cacheKey = 'ai_relevance_' . md5($site1Url . $site2Url);
            $cache = new Cache();
            $cachedResult = $cache->get($cacheKey);
            
            if ($cachedResult !== null) {
                return $cachedResult;
            }
            
            // Preparare la richiesta per l'API OpenAI
            $prompt = "Valuta se questi due siti web sono confrontabili in termini tecnici e di contenuto. ";
            $prompt .= "Sito 1: " . $site1Url . "\n";
            $prompt .= "Sito 2: " . $site2Url . "\n";
            $prompt .= "Rispondi con 'Sì' se sono confrontabili o 'No' se non lo sono, seguito da una breve spiegazione.";
            
            $response = $this->proxyService->forwardRequest('openai', 'completions', [
                'model' => 'text-davinci-003',
                'prompt' => $prompt,
                'max_tokens' => 150,
                'temperature' => 0.3
            ]);
            
            // Analizzare la risposta
            $aiResponse = $response['choices'][0]['text'] ?? '';
            $isRelevant = (stripos($aiResponse, 'Sì') !== false);
            
            // Estrarre la spiegazione
            $explanation = preg_replace('/^(Sì|No)[,.:]?\s*/i', '', $aiResponse);
            
            $result = [
                'relevant' => $isRelevant,
                'reason' => trim($explanation)
            ];
            
            // Salvare in cache (7 giorni)
            $cache->set($cacheKey, $result, 604800);
            
            return $result;
            
        } catch (Exception $e) {
            // In caso di errore, procedere comunque con l'analisi
            return [
                'relevant' => true,
                'reason' => 'Non è stato possibile valutare la pertinenza, si procede con l\'analisi.'
            ];
        }
    }
}
?>
```

## 6. Sistema di Cache

### 6.1 Implementazione
```php
<?php
class Cache {
    private $cachePath;
    
    public function __construct() {
        $this->cachePath = dirname(__FILE__) . '/../cache/';
        
        // Creare la directory di cache se non esiste
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        $data = json_decode($content, true);
        
        // Verificare se il contenuto è scaduto
        if (time() > $data['expires']) {
            $this->delete($key);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $ttl) {
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];
        
        file_put_contents($filename, json_encode($data));
    }
    
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    
    public function clear() {
        $files = glob($this->cachePath . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    private function getCacheFilename($key) {
        // Generare un nome file sicuro basato sulla chiave
        $safeKey = md5($key);
        return $this->cachePath . $safeKey . '.cache';
    }
}
?>
```

### 6.2 Strategia di Caching
- **Risultati di analisi**: 24 ore
- **Verifiche di pertinenza AI**: 7 giorni
- **Chiamate API esterne**: variabile in base al servizio (da 1 ora a 24 ore)
- **Pulizia automatica**: i file scaduti vengono eliminati quando vengono richiesti

## 7. Sicurezza

### 7.1 Sanitizzazione Input
```php
<?php
class Security {
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        } else {
            // Rimuovere caratteri nulli
            $data = str_replace("\0", '', $data);
            
            // Decodificare entità HTML
            $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
            
            // Rimuovere tags HTML
            $data = strip_tags($data);
            
            // Proteggere da SQL injection (se si usasse un database)
            //$data = addslashes($data);
        }
        
        return $data;
    }
    
    public static function validateUrl($url) {
        // Verificare che l'URL sia valido
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function preventCsrf() {
        // Implementare protezione CSRF
        // ...
    }
    
    public static function validateOrigin() {
        // Verificare l'origine della richiesta
        // ...
    }
}
?>
```

### 7.2 Protezione Chiavi API
Le chiavi API sono memorizzate in un file protetto all'esterno della document root:

```php
<?php
// File: config/api_keys.php (protetto)
define('API_KEYS', [
    'google_pagespeed' => 'YOUR_API_KEY_HERE',
    'moz_access_id' => 'YOUR_ACCESS_ID_HERE',
    'moz_secret_key' => 'YOUR_SECRET_KEY_HERE',
    'security_headers' => 'YOUR_API_KEY_HERE',
    'whois_api' => 'YOUR_API_KEY_HERE',
    'wappalyzer' => 'YOUR_API_KEY_HERE',
    'openai' => 'YOUR_API_KEY_HERE'
]);
?>
```

### 7.3 Rate Limiting
```php
<?php
class RateLimiter {
    private $cachePath;
    
    public function __construct() {
        $this->cachePath = dirname(__FILE__) . '/../cache/ratelimit/';
        
        // Creare la directory di cache se non esiste
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
    
    public function canMakeRequest($service) {
        $limits = $this->getServiceLimits();
        
        if (!isset($limits[$service])) {
            // Se non ci sono limiti definiti, consentire la richiesta
            return true;
        }
        
        $limit = $limits[$service];
        $requestsFile = $this->cachePath . $service . '.json';
        
        if (!file_exists($requestsFile)) {
            // Nessuna richiesta precedente
            return true;
        }
        
        $requests = json_decode(file_get_contents($requestsFile), true);
        
        // Pulire le richieste vecchie
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $limit) {
            return $timestamp > ($now - $limit['window']);
        });
        
        // Verificare se il numero di richieste è inferiore al limite
        return count($requests) < $limit['requests'];
    }
    
    public function registerRequest($service) {
        $requestsFile = $this->cachePath . $service . '.json';
        
        $requests = [];
        if (file_exists($requestsFile)) {
            $requests = json_decode(file_get_contents($requestsFile), true);
        }
        
        // Aggiungere la richiesta corrente
        $requests[] = time();
        
        // Salvare le richieste
        file_put_contents($requestsFile, json_encode($requests));
    }
    
    private function getServiceLimits() {
        // Definire i limiti per servizio
        // [requests => numero massimo di richieste, window => finestra temporale in secondi]
        return [
            'pagespeed' => ['requests' => 100, 'window' => 86400], // 100 richieste/giorno
            'moz' => ['requests' => 10, 'window' => 3600], // 10 richieste/ora
            'security_headers' => ['requests' => 50, 'window' => 3600], // 50 richieste/ora
            'whois_api' => ['requests' => 100, 'window' => 86400], // 100 richieste/giorno
            'wappalyzer' => ['requests' => 100, 'window' => 86400], // 100 richieste/giorno
            'openai' => ['requests' => 20, 'window' => 3600] // 20 richieste/ora
        ];
    }
}
?>
```

## 8. Logging e Monitoraggio

### 8.1 Sistema di Logging
```php
<?php
class Logger {
    private $logPath;
    private $logLevel;
    
    // Livelli di log
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    
    public function __construct($logLevel = self::INFO) {
        $this->logPath = dirname(__FILE__) . '/../logs/';
        $this->logLevel = $logLevel;
        
        // Creare la directory di log se non esiste
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    private function log($level, $message, $context = []) {
        if ($level < $this->logLevel) {
            return;
        }
        
        $levelNames = [
            self::DEBUG => 'DEBUG',
            self::INFO => 'INFO',
            self::WARNING => 'WARNING',
            self::ERROR => 'ERROR'
        ];
        
        $date = date('Y-m-d H:i:s');
        $levelName = $levelNames[$level];
        
        // Formattare il messaggio
        $formattedMessage = "[$date] [$levelName] $message";
        
        // Aggiungere il contesto se presente
        if (!empty($context)) {
            $formattedMessage .= ' ' . json_encode($context);
        }
        
        $formattedMessage .= PHP_EOL;
        
        // Scrivere nel file di log
        $logFile = $this->logPath . date('Y-m-d') . '.log';
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
}
?>
```

### 8.2 Monitoraggio delle API
Il sistema implementa un monitoraggio delle API esterne per:
- Tracciare i tempi di risposta
- Registrare gli errori
- Monitorare il rate limiting
- Verificare la disponibilità dei servizi

## 9. Estensibilità

### 9.1 Aggiunta di Nuove API
Il sistema è progettato per consentire l'aggiunta di nuove API esterne seguendo questi passaggi:

1. Aggiungere la configurazione del servizio in `ProxyService::getServiceConfig()`
2. Aggiungere la chiave API in `config/api_keys.php`
3. Implementare eventuali logiche di fallback in `ProxyService::implementFallback()`
4. Aggiungere il rate limiting in `RateLimiter::getServiceLimits()`

### 9.2 Creazione di Nuovi Analizzatori
Per aggiungere un nuovo tipo di analisi:

1. Creare una nuova classe che estende un analizzatore base
2. Implementare i metodi richiesti (analyze, calculateScore)
3. Aggiungere il nuovo analizzatore in AnalyzeController
4. Aggiornare il sistema di punteggio in ResultService