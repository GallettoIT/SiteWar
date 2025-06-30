# Componente di Analisi - Documentazione Tecnica

## 1. Panoramica del Componente

Il componente di analisi è responsabile dell'esecuzione dei test sui siti web, della raccolta dei dati e della preparazione dei risultati per il confronto. È suddiviso in moduli di analisi client-side e server-side che operano in parallelo per garantire un'analisi completa entro il limite di 25 secondi.

## 2. Architettura del Componente

### 2.1 Diagramma delle Classi
```
┌───────────────────┐
│  AnalyzerFactory  │
├───────────────────┤
│ - createAnalyzer()│
│ - getAnalyzer()   │
└───────┬───────────┘
        │
        │ creates
        ▼
┌───────────────────┐         ┌───────────────────┐
│   BaseAnalyzer    │<────────│ AnalysisManager   │
├───────────────────┤         ├───────────────────┤
│ - analyze()       │         │ - runAnalysis()   │
│ - getResults()    │         │ - trackProgress() │
│ - isComplete()    │         │ - getResults()    │
└───────┬───────────┘         └───────────────────┘
        │
        │ extends
┌───────┴───────────┬────────────────┬────────────────┬────────────────┐
│                   │                │                │                │
▼                   ▼                ▼                ▼                ▼
┌───────────────┐ ┌───────────────┐ ┌───────────────┐ ┌───────────────┐ ┌───────────────┐
│ DOMAnalyzer   │ │ SEOAnalyzer   │ │ PerfAnalyzer  │ │ SecAnalyzer   │ │ TechAnalyzer  │
├───────────────┤ ├───────────────┤ ├───────────────┤ ├───────────────┤ ├───────────────┤
│ - parseDOM()  │ │ - checkMeta() │ │ - loadTime()  │ │ - checkSSL()  │ │ - detectTech()│
│ - structMap() │ │ - analyzeHead()│ │ - fcpTime()   │ │ - scanHeaders()│ │ - findFramew()│
└───────────────┘ └───────────────┘ └───────────────┘ └───────────────┘ └───────────────┘
```

### 2.2 Diagramma di Sequenza
```
┌───────┐      ┌───────────────┐      ┌───────────────┐      ┌───────────────┐      ┌───────────────┐
│ User  │      │ AnalysisManager│      │AnalyzerFactory│      │  BaseAnalyzer │      │ External APIs │
└───┬───┘      └───────┬───────┘      └───────┬───────┘      └───────┬───────┘      └───────┬───────┘
    │                  │                      │                      │                      │
    │ startAnalysis    │                      │                      │                      │
    │─────────────────>│                      │                      │                      │
    │                  │                      │                      │                      │
    │                  │ createAnalyzers      │                      │                      │
    │                  │─────────────────────>│                      │                      │
    │                  │                      │                      │                      │
    │                  │                      │ create               │                      │
    │                  │                      │─────────────────────>│                      │
    │                  │                      │                      │                      │
    │                  │ runAnalysis          │                      │                      │
    │                  │─────────────────────────────────────────────>                      │
    │                  │                      │                      │                      │
    │                  │                      │                      │ API Requests         │
    │                  │                      │                      │─────────────────────>│
    │                  │                      │                      │                      │
    │                  │                      │                      │ API Responses        │
    │                  │                      │                      │<─────────────────────│
    │                  │                      │                      │                      │
    │ updateProgress   │                      │                      │                      │
    │<─────────────────│                      │                      │                      │
    │                  │                      │                      │                      │
    │                  │ getResults           │                      │                      │
    │                  │─────────────────────────────────────────────>                      │
    │                  │                      │                      │                      │
    │                  │ results              │                      │                      │
    │                  │<─────────────────────────────────────────────                      │
    │                  │                      │                      │                      │
    │ finalResults     │                      │                      │                      │
    │<─────────────────│                      │                      │                      │
    │                  │                      │                      │                      │
```

## 3. Moduli di Analisi

### 3.1 Analisi DOM e Struttura (Client-side)
- **Responsabilità**: Analizzare la struttura del documento HTML, la gerarchia degli elementi e la qualità del markup
- **Metriche chiave**:
  - Struttura dei heading (H1-H6)
  - Rapporto testo/codice
  - Uso corretto di elementi semantici
  - Accessibilità della struttura

### 3.2 Analisi SEO (Client+Server)
- **Responsabilità**: Valutare l'ottimizzazione per i motori di ricerca
- **Metriche chiave**:
  - Meta tag (title, description)
  - URL structure
  - Alt text per immagini
  - Schema markup
  - Sitemap e robots.txt
  - Canonical URLs

### 3.3 Analisi Performance (Client-side)
- **Responsabilità**: Misurare la velocità e l'efficienza del caricamento
- **Metriche chiave**:
  - First Contentful Paint (FCP)
  - Largest Contentful Paint (LCP)
  - Time to Interactive (TTI)
  - Cumulative Layout Shift (CLS)
  - First Input Delay (FID)
  - Resource loading times

### 3.4 Analisi Sicurezza (Server-side)
- **Responsabilità**: Verificare la sicurezza del sito web
- **Metriche chiave**:
  - HTTP security headers
  - SSL/TLS configuration
  - Content Security Policy
  - Cross-site scripting vulnerabilities
  - HSTS implementation
  - Cookie security

### 3.5 Analisi Tecnologica (Client+Server)
- **Responsabilità**: Identificare tecnologie, framework e librerie utilizzate
- **Metriche chiave**:
  - Framework frontend
  - CMS in uso
  - Server-side technologies
  - JavaScript libraries
  - Versioni del software
  - API utilizzate

## 4. Implementazione

### 4.1 Pattern di Design
- **Factory Method**: Per creare i diversi analizzatori
- **Strategy Pattern**: Per implementare diverse strategie di analisi
- **Observer Pattern**: Per notificare il progresso dell'analisi
- **Adapter Pattern**: Per interfacciare le API esterne

### 4.2 Pseudocodice - Client-side Analyzer
```javascript
// Implementazione del Module Pattern
var SiteAnalyzer = (function() {
    // Variabili private
    var results = {};
    var progress = 0;
    var analyzersCount = 0;
    var completedAnalyzers = 0;
    
    // Metodi privati
    function updateProgress() {
        progress = (completedAnalyzers / analyzersCount) * 100;
        EventBus.trigger('analysis.progress', progress);
    }
    
    // Interface pubblica
    return {
        analyze: function(url) {
            results = {};
            progress = 0;
            completedAnalyzers = 0;
            
            // Creare gli analizzatori usando il Factory Pattern
            var domAnalyzer = AnalyzerFactory.createDOMAnalyzer();
            var perfAnalyzer = AnalyzerFactory.createPerformanceAnalyzer();
            var seoAnalyzer = AnalyzerFactory.createSEOAnalyzer();
            
            analyzersCount = 3; // Base client analyzers
            
            // Avviare le analisi in parallelo
            domAnalyzer.analyze(url).then(function(domResults) {
                results.dom = domResults;
                completedAnalyzers++;
                updateProgress();
            });
            
            perfAnalyzer.analyze(url).then(function(perfResults) {
                results.performance = perfResults;
                completedAnalyzers++;
                updateProgress();
            });
            
            seoAnalyzer.analyze(url).then(function(seoResults) {
                results.seo = seoResults;
                completedAnalyzers++;
                updateProgress();
            });
            
            // Richiedere risultati dal server
            $.ajax({
                url: 'server/api/analyze.php',
                method: 'POST',
                data: {url: url},
                success: function(serverResults) {
                    results.server = serverResults;
                    EventBus.trigger('analysis.complete', results);
                }
            });
        },
        
        getResults: function() {
            return results;
        },
        
        getProgress: function() {
            return progress;
        }
    };
})();
```

### 4.3 Pseudocodice - Server-side Analyzer
```php
<?php
// Implementazione del Pattern Strategy
abstract class ServerAnalyzer {
    protected $url;
    
    public function __construct($url) {
        $this->url = $url;
    }
    
    abstract public function analyze();
}

class SecurityAnalyzer extends ServerAnalyzer {
    public function analyze() {
        $results = [];
        
        // Check SSL certificate
        $sslCheck = $this->checkSSL($this->url);
        $results['ssl'] = $sslCheck;
        
        // Check security headers
        $headers = $this->getHeaders($this->url);
        $results['headers'] = $this->analyzeHeaders($headers);
        
        return $results;
    }
    
    private function checkSSL($url) {
        // Implementation
    }
    
    private function getHeaders($url) {
        // Implementation
    }
    
    private function analyzeHeaders($headers) {
        // Implementation
    }
}

class APIController {
    public function analyzeAction() {
        $url = $_POST['url'];
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL']);
            return;
        }
        
        try {
            // Creare gli analizzatori usando il Factory Pattern
            $securityAnalyzer = new SecurityAnalyzer($url);
            $whoisAnalyzer = new WHOISAnalyzer($url);
            $advancedSeoAnalyzer = new AdvancedSeoAnalyzer($url);
            
            // Eseguire le analisi in parallelo (se possibile)
            $securityResults = $securityAnalyzer->analyze();
            $whoisResults = $whoisAnalyzer->analyze();
            $seoResults = $advancedSeoAnalyzer->analyze();
            
            // Combinare i risultati
            $results = [
                'security' => $securityResults,
                'whois' => $whoisResults,
                'advancedSeo' => $seoResults
            ];
            
            // Restituire i risultati come JSON
            header('Content-Type: application/json');
            echo json_encode($results);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>
```

## 5. Metriche e Sistema di Punteggio

### 5.1 Categorie di Valutazione
Ogni sito web riceve un punteggio in ciascuna delle seguenti categorie:

| Categoria | Peso | Descrizione |
|-----------|------|-------------|
| Performance | 30% | Velocità e reattività |
| SEO | 25% | Ottimizzazione per motori di ricerca |
| Sicurezza | 25% | Protezione e conformità |
| Tecnica | 20% | Qualità del codice e tecnologie |

### 5.2 Formula di Punteggio
Il punteggio finale viene calcolato come:

```
Score = (Performance * 0.3) + (SEO * 0.25) + (Security * 0.25) + (Technical * 0.2)
```

Dove ogni categoria è valutata da 0 a 100.

### 5.3 Determinazione del Vincitore
Il sito con il punteggio totale più alto viene dichiarato vincitore. In caso di parità, si considerano in ordine:
1. Punteggio Performance
2. Punteggio Sicurezza
3. Punteggio SEO

## 6. Integrazione con External APIs

### 6.1 API Utilizzate
- **Google PageSpeed Insights**: Performance metrics
- **Moz API**: SEO metrics
- **Security Headers**: Security analysis
- **WHOIS API**: Domain information
- **W3C Validator**: HTML/CSS validation
- **Wappalyzer API**: Technology detection

### 6.2 Rate Limiting e Fallback
- Implementazione di caching per ridurre chiamate API
- Sistema di rate limiting per evitare superamento quote
- Strategie di fallback in caso di API non disponibili

## 7. Ottimizzazione Performance

### 7.1 Strategie di Parallelizzazione
- Esecuzione parallela di analizzatori indipendenti
- Distribuzione del carico tra client e server
- Prioritizzazione delle analisi rapide

### 7.2 Caching
- Cache dei risultati per URL recentemente analizzati
- Cache delle risposte API esterne
- Strategia di invalidazione basata su tempo (24 ore)

## 8. Estensibilità

### 8.1 Aggiunta di Nuovi Analizzatori
Il sistema è progettato per consentire l'aggiunta di nuovi analizzatori seguendo questi passaggi:
1. Creare una nuova classe che estende BaseAnalyzer
2. Implementare i metodi richiesti (analyze, getResults, isComplete)
3. Aggiungere il nuovo analizzatore al factory
4. Aggiornare il sistema di punteggio se necessario

### 8.2 Integrazione con Nuove API
Per integrare nuove API esterne:
1. Creare un adapter per l'API
2. Implementare la gestione degli errori e il rate limiting
3. Aggiungere la gestione della cache
4. Configurare le credenziali nel sistema