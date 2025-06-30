# Documentazione Tecnica - Site War

## 1. Architettura del Sistema

### 1.1 Visione d'insieme
Site War è un'applicazione web basata su un'architettura client-server con elaborazione distribuita. Il sistema mira a confrontare due siti web attraverso diversi parametri tecnici, presentando il confronto come una "guerra" tra siti con un vincitore finale.

### 1.2 Diagramma architetturale
```
+-------------------+        +--------------------+
|                   |        |                    |
|  CLIENT BROWSER   |<------>|  PHP SERVER        |
|                   |        |                    |
+-------------------+        +--------------------+
|                   |        |                    |
| - HTML/CSS/JS     |        | - API Controller   |
| - jQuery          |        | - Proxy Service    |
| - Bootstrap       |        | - AI Validator     |
| - Anime.js        |        | - Advanced         |
| - Chart.js        |        |   Analyzers        |
| - Client Analyzers|        | - Result Generator |
|                   |        |                    |
+-------------------+        +--------------------+
         ^                            ^
         |                            |
         v                            v
+---------------------------------------------------+
|                EXTERNAL SERVICES                   |
|                                                   |
| - Google PageSpeed Insights   - HTML Validator    |
| - Moz API                     - CSS Validator     |
| - Security Headers            - OpenAI API        |
| - WHOIS API                                      |
|                                                   |
+---------------------------------------------------+
```

## 2. Componenti del Sistema

### 2.1 Frontend (Client)

#### 2.1.1 Moduli Principali
- **Interfaccia Utente**: Gestisce l'interazione con l'utente e la visualizzazione dei risultati
- **Motore di Analisi Client**: Esegue analisi lato client come DOM parsing, performance testing
- **Visualizzatore Animazioni**: Crea le animazioni per rappresentare la "guerra" tra siti
- **Sistema di Confronto**: Compara i risultati delle analisi per determinare punti di forza/debolezza

#### 2.1.2 Diagramma delle Classi - Frontend
```
+----------------+       +----------------+       +-------------------+
| UIController   |------>| AnimationEngine|------>| ResultDashboard   |
+----------------+       +----------------+       +-------------------+
| - initUI()     |       | - initAnims()  |       | - renderResults() |
| - validateURLs()|      | - showBattle() |       | - showComparison()|
| - submitForm() |       | - updateAnim() |       | - declareWinner() |
+----------------+       +----------------+       +-------------------+
        |                       ^                         ^
        v                       |                         |
+----------------+       +------+-------+       +---------+-------+
| AnalyzerFactory|------>| BaseAnalyzer |<------| ComparisonEngine |
+----------------+       +--------------+       +-----------------+
| - createDOM()  |       | - analyze()  |       | - compare()      |
| - createPerf() |       | - getResults()|      | - calculateScore()|
| - createSEO()  |       | - isComplete()|      | - findWinner()    |
+----------------+       +--------------+       +-----------------+
        |                       ^
        v                       |
+----------------+    +---------+---------+    +----------------+
| APIConnector   |    | Concrete Analyzers|    | ReportGenerator|
+----------------+    +-------------------+    +----------------+
| - fetchAPI()   |    | - DOMAnalyzer     |    | - generateHTML()|
| - sendRequest()|    | - PerfAnalyzer    |    | - exportCSV()   |
| - handleError()|    | - SEOAnalyzer     |    | - saveReport()  |
+----------------+    | - SecurityAnalyzer|    +----------------+
                     +-------------------+
```

### 2.2 Backend (Server)

#### 2.2.1 Moduli Principali
- **API Controller**: Gestisce e orchestra le richieste in entrata
- **Proxy Service**: Inoltro sicuro delle richieste a API esterne
- **AI Validator**: Valuta la pertinenza del confronto tra i siti
- **Analizzatori Avanzati**: Esegue analisi che richiedono accesso server
- **Generatore Risultati**: Elabora i dati finali e determina il vincitore

#### 2.2.2 Diagramma delle Classi - Backend
```
+----------------+       +----------------+       +-------------------+
| APIController  |------>| RequestHandler |------>| ResultsProcessor  |
+----------------+       +----------------+       +-------------------+
| - route()      |       | - validateReq()|       | - processResults()|
| - authenticate()|      | - parseParams()|       | - aggregateData() |
| - respond()    |       | - logRequest() |       | - scoreWebsites() |
+----------------+       +----------------+       +-------------------+
        |                       ^                         ^
        v                       |                         |
+----------------+       +------+-------+       +---------+-------+
| ServiceFactory |------>| BaseService  |<------| AIValidator      |
+----------------+       +--------------+       +-----------------+
| - createProxy()|       | - execute()  |       | - validateURLs() |
| - createAI()   |       | - getResult()|       | - checkRelevance()|
| - createScan() |       | - hasError() |       | - explainResult() |
+----------------+       +--------------+       +-----------------+
        |                       ^
        v                       |
+----------------+    +---------+---------+    +----------------+
| ConfigManager  |    | Concrete Services |    | SecurityManager|
+----------------+    +-------------------+    +----------------+
| - loadConfig() |    | - ProxyService    |    | - sanitizeInput()|
| - getAPIKey()  |    | - ScanService     |    | - validateOrigin()|
| - getCacheTime()|   | - AIService       |    | - rateLimit()     |
+----------------+    | - ReportService   |    +----------------+
                     +-------------------+
```

## 3. Processi e Flussi

### 3.1 Diagramma di Sequenza - Processo di Analisi Completo
```
┌─────┐          ┌─────────┐          ┌────────┐          ┌──────────┐          ┌──────────┐
│User │          │Frontend │          │Backend │          │AI Service│          │Ext APIs  │
└──┬──┘          └────┬────┘          └───┬────┘          └────┬─────┘          └────┬─────┘
   │  Input URLs    │                     │                    │                     │
   │───────────────>│                     │                     │                     │
   │                │ Validate Format     │                     │                     │
   │                │─────────────────────│                     │                     │
   │                │                     │ Check Relevance     │                     │
   │                │                     │────────────────────>│                     │
   │                │                     │                     │ Evaluate URLs       │
   │                │                     │                     │───────────────────>│
   │                │                     │                     │                     │
   │                │                     │                     │<───────────────────│
   │                │                     │<────────────────────│                     │
   │                │<─────────────────────│                     │                     │
   │                │                     │                     │                     │
   │                │ Client Analysis     │                     │                     │
   │                │─────────────────────│                     │                     │
   │                │                     │ Server Analysis     │                     │
   │                │                     │────────────────────>│                     │
   │                │                     │                     │───────────────────>│
   │                │  Show Progress      │                     │                     │
   │<───────────────│                     │                     │                     │
   │                │                     │                     │<───────────────────│
   │                │                     │<────────────────────│                     │
   │                │<─────────────────────│                     │                     │
   │                │ Process Results     │                     │                     │
   │                │──────┐              │                     │                     │
   │                │      │              │                     │                     │
   │                │<─────┘              │                     │                     │
   │  Final Results │                     │                     │                     │
   │<───────────────│                     │                     │                     │
   │                │                     │                     │                     │
```

### 3.2 Diagramma di Stato - Processo di Analisi
```
┌───────────────┐     ┌────────────────┐     ┌────────────────┐
│               │     │                │     │                │
│ URL Input     ├────>│ Validation     ├────>│ Analysis       │
│               │     │                │     │                │
└───────────────┘     └────────────────┘     └────────┬───────┘
                                                     │
┌───────────────┐     ┌────────────────┐     ┌───────▼───────┐
│               │     │                │     │                │
│ Results       │<────┤ Comparison     │<────┤ Processing     │
│               │     │                │     │                │
└───────────────┘     └────────────────┘     └────────────────┘
```

## 4. Analizzatori e Metriche

### 4.1 Tipi di Analisi
1. **Analisi Generica**
   - Linguaggi frontend utilizzati
   - Framework e librerie
   - CMS e versioni
   - Server e infrastruttura
   - Informazioni IP e DNS

2. **Analisi SEO**
   - Meta tag (title, description, keywords)
   - Struttura headings (h1, h2, h3...)
   - Alt text per immagini
   - URL structure
   - Sitemap e robots.txt
   - Schema markup e dati strutturati

3. **Analisi Vulnerabilità**
   - HTTP security headers
   - SSL/TLS configuration
   - Input validation
   - Content Security Policy
   - Cross-site scripting potenziali
   - Outdated software

4. **Analisi Performance**
   - Page load time
   - First contentful paint
   - Time to interactive
   - Largest contentful paint
   - Cumulative layout shift
   - Ottimizzazione asset (minification, compression)
   - Cache utilization

### 4.2 Sistema di Punteggio
Ogni categoria di analisi contribuisce al punteggio finale:
- Performance: 30%
- SEO: 25%
- Sicurezza: 25%
- Aspetti tecnici: 20%

## 5. Casi d'uso

### 5.1 Diagramma dei Casi d'Uso
```
┌───────────────────────────────────────────────────┐
│                     Site War                       │
│                                                   │
│  ┌───────────┐        ┌───────────────────────┐   │
│  │           │        │                       │   │
│  │   Input   │        │  Analizza Siti Web    │   │
│  │   URLs    │───────>│                       │   │
│  │           │        │                       │   │
│  └───────────┘        └───────────┬───────────┘   │
│         │                         │               │
│         │                         │               │
│         v                         v               │
│  ┌───────────┐        ┌───────────────────────┐   │
│  │           │        │                       │   │
│  │ Visualizza│<───────│  Confronta Risultati  │   │
│  │ Battaglia │        │                       │   │
│  │           │        │                       │   │
│  └───────────┘        └───────────┬───────────┘   │
│         │                         │               │
│         │                         │               │
│         v                         v               │
│  ┌───────────┐        ┌───────────────────────┐   │
│  │           │        │                       │   │
│  │ Visualizza│<───────│  Determina Vincitore  │   │
│  │ Dettagli  │        │                       │   │
│  │           │        │                       │   │
│  └───────────┘        └───────────────────────┘   │
│                                                   │
└───────────────────────────────────────────────────┘
```

### 5.2 Descrizione Casi d'Uso Principali

#### UC1: Inserimento URL e Validazione
**Attore principale**: Utente  
**Pre-condizioni**: Nessuna  
**Flusso base**:
1. L'utente accede alla piattaforma Site War
2. L'utente inserisce gli URL dei due siti da confrontare
3. Il sistema valida il formato degli URL
4. Il sistema utilizza l'AI per verificare la pertinenza del confronto
5. Se gli URL sono validi e il confronto è pertinente, il sistema procede all'analisi

#### UC2: Analisi dei Siti
**Attore principale**: Sistema  
**Pre-condizioni**: URL validi e pertinenti inseriti  
**Flusso base**:
1. Il sistema avvia l'analisi lato client per i componenti accessibili dal browser
2. Il sistema avvia in parallelo l'analisi lato server per i componenti più complessi
3. Durante l'analisi, il sistema visualizza animazioni di "battaglia" tra i siti
4. Man mano che le analisi vengono completate, i risultati parziali vengono mostrati
5. Il sistema completa tutte le analisi entro 25 secondi

#### UC3: Visualizzazione dei Risultati e Proclamazione Vincitore
**Attore principale**: Utente  
**Pre-condizioni**: Analisi completata  
**Flusso base**:
1. Il sistema aggrega tutti i risultati delle analisi
2. Il sistema calcola il punteggio complessivo per ogni sito
3. Il sistema determina e proclama il vincitore della "guerra"
4. L'utente può visualizzare i dettagli comparativi per ogni categoria
5. L'utente può esplorare i punti di forza e debolezza di ciascun sito

## 6. Interfaccia Utente

### 6.1 Wireframes Principali
[Da creare: wireframes delle schermate principali]

### 6.2 Flusso UI
```
┌───────────┐     ┌───────────┐     ┌───────────┐     ┌───────────┐
│           │     │           │     │           │     │           │
│  Home     ├────>│ URL Input ├────>│ Analysis  ├────>│ Results   │
│  Page     │     │ Form      │     │ Progress  │     │ Dashboard │
│           │     │           │     │           │     │           │
└───────────┘     └───────────┘     └───────────┘     └───────────┘
                                                            │
                                                            v
                                                     ┌───────────┐
                                                     │           │
                                                     │ Detailed  │
                                                     │ Reports   │
                                                     │           │
                                                     └───────────┘
```

## 7. Sicurezza

### 7.1 Misure di Sicurezza
- Implementazione di CSP (Content Security Policy)
- Sanitizzazione input utente
- Protezione da attacchi XSS e CSRF
- Obfuscation del codice JavaScript critico
- Utilizzo di HTTPS per tutte le comunicazioni
- Protezione delle API key

### 7.2 Gestione dei Rischi
[Da dettagliare: matrice di rischio e strategie di mitigazione]

## 8. Piano di Testing

### 8.1 Test Unitari
- Test delle singole componenti di analisi
- Test dei moduli di confronto
- Test del sistema di punteggio

### 8.2 Test di Integrazione
- Test dell'integrazione client-server
- Test delle chiamate API esterne
- Test del flusso completo di analisi

### 8.3 Test di Usabilità
- Test su diversi browser e dispositivi
- Test di accessibilità WCAG 2.1 AA
- Test delle animazioni e della user experience

## 9. Pianificazione e Roadmap

### 9.1 Fasi di Sviluppo
1. **Fase 1**: MVP con analisi base e confronto
2. **Fase 2**: Miglioramento delle animazioni e UX
3. **Fase 3**: Implementazione analisi avanzate
4. **Fase 4**: Integrazione con ulteriori API esterne
5. **Fase 5**: Ottimizzazioni performance e affinamento

### 9.2 Timeline
[Da dettagliare: timeline di sviluppo con milestone]