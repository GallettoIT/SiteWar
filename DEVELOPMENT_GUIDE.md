# Site War - Guida allo Sviluppo

Questa guida fornisce istruzioni dettagliate per lo sviluppo del progetto Site War, un tool di web testing comparativo che presenta l'analisi come una "guerra" tra siti web.

## Prerequisiti

- PHP 7.4 o superiore
- Server web (Apache/Nginx)
- Conoscenza di HTML5, CSS3 e JavaScript
- Account e chiavi API per i servizi esterni utilizzati

## Setup Ambiente di Sviluppo

1. Clonare il repository:
```
git clone https://github.com/yourusername/site-war.git
```

2. Configurare il server web per puntare alla directory principale del progetto

3. Copiare il file di configurazione delle API:
```
cp server/config/api_keys.example.php server/config/api_keys.php
```

4. Modificare `server/config/api_keys.php` per inserire le proprie chiavi API

5. Assicurarsi che le directory `server/cache/data` e `server/cache/ratelimit` siano scrivibili dal server web:
```
chmod -R 755 server/cache
```

## Struttura del Progetto

```
site-war/
│
├── assets/                   # Risorse statiche
│   ├── css/                  # Fogli di stile
│   │   ├── main.css          # Stili principali
│   │   └── animations.css    # Animazioni dedicate
│   ├── js/                   # JavaScript
│   │   ├── App.js            # Entry point
│   │   ├── EventBus.js       # Gestore eventi
│   │   ├── APIClient.js      # Client API
│   │   ├── FormUI.js         # Interfaccia form
│   │   ├── BattleUI.js       # Animazioni battaglia 
│   │   └── ResultsUI.js      # Dashboard risultati
│   └── img/                  # Immagini e icone
│
├── server/                   # Backend PHP
│   ├── api/                  # Endpoint API
│   │   ├── index.php         # Entry point API
│   │   └── controllers/      # Controller specifici
│   ├── config/               # Configurazioni
│   │   ├── api_keys.php      # Chiavi API (gitignore)
│   │   └── services.php      # Config servizi
│   ├── core/                 # Funzionalità core
│   │   ├── APIController.php # Controller principale
│   │   ├── Controller.php    # Interfaccia controller
│   │   └── ServiceFactory.php # Factory per servizi
│   ├── services/             # Servizi business logic
│   │   ├── analyzers/        # Analizzatori specifici
│   │   ├── AnalysisManager.php # Gestore analisi
│   │   ├── BaseService.php   # Classe base servizi
│   │   ├── ProxyService.php  # Proxy API esterne
│   │   └── AIService.php     # Integrazione OpenAI
│   ├── utils/                # Funzioni di utilità
│   │   ├── Cache.php         # Sistema cache
│   │   ├── RateLimiter.php   # Limitatore richieste
│   │   └── Security.php      # Funzioni sicurezza
│   └── cache/                # Directory cache
│       ├── data/             # Cache dati
│       └── ratelimit/        # Cache rate limit
│
├── docs/                     # Documentazione tecnica
├── index.php                 # Entry point applicazione
└── .htaccess                 # Configurazione Apache
```

## Convenzioni di Codice

### PHP
- PSR-12 per stile codice
- Documentazione PHPDoc per classi e metodi
- File encoding: UTF-8
- camelCase per nomi di variabili e metodi
- PascalCase per nomi di classi e interfacce

### JavaScript
- Seguire Airbnb JavaScript Style Guide
- Module Pattern per organizzazione codice
- camelCase per nomi di variabili e funzioni
- PascalCase per costruttori e moduli principali

### CSS
- Metodologia BEM (Block Element Modifier)
- Uso di variabili CSS
- Mobile-first responsive design
- Prefisso `sw-` per classi specifiche del progetto

## Flusso di Sviluppo

1. **Creazione/modifica dei file**:
   - Seguire le convenzioni di codice
   - Implementare un solo modulo/feature alla volta
   - Testare localmente dopo ogni modifica significativa

2. **Testing**:
   - Verificare funzionalità in diversi browser/device
   - Controllare performance (rispetto limite 25 secondi)
   - Validare corretto funzionamento API esterne

3. **Documentazione**:
   - Aggiornare commenti e DocBlock
   - Mantenere aggiornati i file in `/docs`

## Componenti Backend (PHP)

### APIController
Punto di ingresso per tutte le richieste API. Gestisce routing e risposta.

```php
class APIController {
    public function processRequest();
    private function getRequestPath();
    private function getControllerFromPath($path);
    private function getRequestParams($method);
    private function sendResponse($statusCode, $data);
}
```

### Controllers
Implementano la logica specifica per ciascun endpoint API.

```php
interface Controller {
    public function handleRequest($method, $params);
}

class AnalyzeController implements Controller {
    public function handleRequest($method, $params);
    private function completeAnalysis($analysisId, $results);
}

class ValidateController implements Controller {
    public function handleRequest($method, $params);
    private function validateUrl($url);
    private function checkRelevance($url1, $url2);
}

class ReportController implements Controller {
    public function handleRequest($method, $params);
    private function getAnalysisStatus($analysisId);
    private function generateReport($results, $format);
}
```

### AnalysisManager
Coordina l'intero processo di analisi dei siti web.

```php
class AnalysisManager {
    public function startAnalysis($analysisId);
    private function performAnalysisAsync($analysisId);
    private function updateAnalysisStatus($analysisId, $status, $progress, $message, $results);
    private function processResults($results1, $results2);
    private function compareResults($site1Results, $site2Results);
    private function calculateFinalScores($results);
}
```

### Analyzers
Eseguono analisi specifiche sui siti web.

```php
abstract class BaseAnalyzer {
    public function analyze();
    protected abstract function doAnalyze();
    protected function implementFallback();
    public function getResults();
}

class SEOAnalyzer extends BaseAnalyzer {
    protected function doAnalyze();
    private function analyzeMeta();
    private function analyzeHeadings();
    private function analyzeImages();
    // Altri metodi specifici...
}

class SecurityAnalyzer extends BaseAnalyzer {
    protected function doAnalyze();
    private function analyzeSecurityHeaders();
    private function analyzeSSL();
    private function analyzeVulnerabilities();
    // Altri metodi specifici...
}

// Implementare anche:
class PerformanceAnalyzer extends BaseAnalyzer { }
class TechnologyAnalyzer extends BaseAnalyzer { }
```

### Services
Forniscono funzionalità e integrazioni specifiche.

```php
abstract class BaseService {
    public function execute();
    abstract protected function implementFallback();
    public function getResult();
}

class ProxyService extends BaseService {
    public function setParams($params);
    private function getApiKey();
    private function processResponse($response);
}

class AIService extends BaseService {
    public function setPrompt($prompt);
    private function getApiKey();
}
```

## Componenti Frontend (JavaScript)

### App
Controller principale dell'applicazione.

```javascript
const SiteWar = {}; // Namespace globale

SiteWar.App = (function() {
    // Variabili private
    let _initialized = false;
    
    // Metodi privati
    function _handleSubmit(event) { }
    
    // API pubblica
    return {
        init: function() { },
        startAnalysis: function(url1, url2) { },
        showResults: function(results) { }
    };
})();
```

### EventBus
Sistema di comunicazione tra moduli tramite eventi.

```javascript
SiteWar.EventBus = (function() {
    const _subscribers = {};
    
    return {
        subscribe: function(event, callback, context) { },
        unsubscribe: function(event, callback) { },
        publish: function(event, data) { }
    };
})();
```

### APIClient
Gestisce le comunicazioni con il backend.

```javascript
SiteWar.APIClient = (function() {
    const _endpoints = {
        validate: '/api/validate',
        analyze: '/api/analyze',
        progress: '/api/progress'
    };
    
    return {
        validateUrls: function(url1, url2) { },
        analyzeUrls: function(url1, url2) { },
        getProgress: function(analysisId) { },
        getResults: function(analysisId, format) { }
    };
})();
```

### UI Modules
Gestiscono l'interfaccia utente.

```javascript
SiteWar.FormUI = (function() {
    return {
        init: function() { },
        validate: function() { },
        getValues: function() { },
        setError: function(message) { }
    };
})();

SiteWar.BattleUI = (function() {
    return {
        init: function() { },
        startBattle: function(site1, site2) { },
        updateProgress: function(progress, status) { },
        showWinner: function(winner) { }
    };
})();

SiteWar.ResultsUI = (function() {
    return {
        init: function() { },
        showResults: function(results) { },
        createCharts: function(data) { },
        exportResults: function(format) { }
    };
})();
```

## API Endpoints

### Validazione URL
- **Endpoint**: `/api/validate`
- **Metodo**: POST
- **Parametri**: `url1`, `url2`
- **Risposta**: 
```json
{
  "url1": {
    "url": "https://example.com",
    "valid": true,
    "reachable": true,
    "statusCode": 200,
    "contentType": "text/html"
  },
  "url2": {
    "url": "https://sample.com",
    "valid": true,
    "reachable": true,
    "statusCode": 200,
    "contentType": "text/html"
  },
  "comparison": {
    "relevant": true,
    "relevanceScore": 75,
    "categories": ["ecommerce", "technology"],
    "message": "Entrambi i siti sono marketplace tecnologici."
  },
  "valid": true
}
```

### Analisi
- **Endpoint**: `/api/analyze`
- **Metodo**: POST
- **Parametri**: `url1`, `url2`
- **Risposta**: 
```json
{
  "analysisId": "analysis_60f8c4e2a1b3c",
  "status": "initiated",
  "message": "Analisi avviata con successo"
}
```

### Progresso
- **Endpoint**: `/api/progress`
- **Metodo**: GET
- **Parametri**: `analysisId`
- **Risposta**: 
```json
{
  "analysisId": "analysis_60f8c4e2a1b3c",
  "status": "in_progress",
  "progress": 65,
  "message": "Analisi SEO in corso...",
  "timestamp": 1626864000
}
```

### Risultati
- **Endpoint**: `/api/progress`
- **Metodo**: GET
- **Parametri**: `analysisId`, `format` (opzionale: json, csv, html)
- **Risposta**: Dati completi dell'analisi con confronto e vincitore

## Workflow di Analisi

1. **Validazione Input**:
   - Frontend valida format URL con regex
   - Backend valida raggiungibilità e contenuto
   - AI valuta pertinenza del confronto

2. **Avvio Analisi**:
   - Frontend mostra animazione iniziale
   - Backend crea ID sessione unica
   - Processo asincrono avvia analizzatori

3. **Esecuzione Analisi**:
   - AnalysisManager coordina analizzatori
   - Analisi eseguite in parallelo dove possibile
   - Stato aggiornato in tempo reale

4. **Polling Stato**:
   - Frontend controlla avanzamento ogni 2-3 secondi
   - Animazione battaglia aggiornata in base a progresso
   - Aggiornamento UI per ogni fase completata

5. **Completamento**:
   - Risultati aggregati e confrontati
   - Punteggi calcolati e vincitore determinato
   - Dashboard risultati visualizzata

## Sistema di Punteggio

Il punteggio finale è una media ponderata delle seguenti categorie:
- Performance: 30% (FCP, LCP, TTI, CLS)
- SEO: 25% (meta tag, headings, links, content)
- Sicurezza: 25% (headers, SSL, vulnerabilità, cookies)
- Aspetti tecnici: 20% (tecnologie, DOM, accessibilità)

Formula: `Score = (Performance * 0.3) + (SEO * 0.25) + (Security * 0.25) + (Technical * 0.2)`

## Integrazione API Esterne

Il sistema si integra con le seguenti API attraverso ProxyService:

1. **Google PageSpeed Insights**: Metriche performance
2. **Moz API**: Analisi SEO
3. **Security Headers**: Controlli sicurezza
4. **WHOIS API**: Informazioni domini
5. **W3C Validator**: Validazione HTML/CSS
6. **OpenAI API**: Validazione pertinenza

Principi di integrazione:
- Protezione chiavi API (mai esposte al client)
- Caching intelligente (riutilizzo risultati)
- Rate limiting per prevenire abusi
- Strategie di fallback per gestire errori

## Ottimizzazione Performance

- **Distribuzione carico**: 65% client, 25% server, 10% API esterne
- **Caching multi-livello**: 
  - Cache lato server (risultati analisi)
  - Cache lato client (dati intermedi)
- **Esecuzione parallela**:
  - Analizzatori indipendenti eseguiti contemporaneamente
  - Prioritizzazione analisi più veloci
- **Timeout gestiti**:
  - Nessuna analisi può bloccare oltre il limite
  - Fallback intelligenti per componenti troppo lenti

## Sicurezza

- **Protezione input**:
  - Sanitizzazione parametri
  - Validazione URL contro iniezioni
- **Protezione API**:
  - Chiavi mai esposte al client
  - Rate limiting
- **Contenuto sicuro**:
  - CSP implementato
  - Controlli HTTPS per API esterne

## Risorse Utili

- [Documentazione PHP](https://www.php.net/docs.php)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.0/getting-started/introduction/)
- [Chart.js Documentation](https://www.chartjs.org/docs/latest/)
- [Anime.js Documentation](https://animejs.com/documentation/)
- [Google PageSpeed API](https://developers.google.com/speed/docs/insights/v5/get-started)
- [OpenAI API](https://platform.openai.com/docs/api-reference)