# Frontend Component - Diagrammi UML e Specifiche

## 1. Diagramma delle Classi Frontend

```
┌───────────────────────────────────────────────────────────────────────┐
│                         SiteWarApplication                            │
├───────────────────────────────────────────────────────────────────────┤
│ -currentSection: string                                               │
│ -analysisResults: object                                              │
├───────────────────────────────────────────────────────────────────────┤
│ +init(): void                                                         │
│ +showSection(sectionId: string): void                                 │
│ -handleFormSubmit(event: Event): void                                 │
│ -startAnalysis(site1Url: string, site2Url: string): void              │
│ -handleAnalysisComplete(event: Event, results: object): void          │
│ -handleAnalysisProgress(event: Event, progress: number): void         │
│ -resetApp(): void                                                     │
└─────────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  │ manages
                                  │
                                  ▼
┌───────────────────────────────────────────────────────────────────────┐
│                            EventBus                                   │
├───────────────────────────────────────────────────────────────────────┤
│ -subscribers: Map<string, Function[]>                                 │
├───────────────────────────────────────────────────────────────────────┤
│ +subscribe(event: string, callback: Function): void                   │
│ +unsubscribe(event: string, callback: Function): void                 │
│ +publish(event: string, data: any): void                              │
└─────────────────────────────────┬─────────────────────────────────────┘
                                  │
                                  │ notifies
                                  │
                 ┌────────────────┼────────────────┐
                 │                │                │
                 ▼                ▼                ▼
┌────────────────────┐  ┌────────────────────┐  ┌────────────────────┐
│      FormUI        │  │     BattleUI       │  │     ResultsUI      │
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ -form: HTMLElement │  │ -container: Element│  │ -container: Element│
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ +init(): void      │  │ +init(): void      │  │ +init(): void      │
│ +validateUrls(): bool│ │ +startBattle(): void│ │ +displayResults(): void│
│ +handleSubmit(): void│ │ +updateBattle(): void│ +createCharts(): void│
│ +reset(): void      │  │ +reset(): void      │  │ +reset(): void      │
└────────────────────┘  └──────────┬─────────┘  └──────────┬─────────┘
                                   │                       │
                                   ▼                       ▼
┌────────────────────┐  ┌────────────────────┐  ┌────────────────────┐
│  AnalysisUI        │  │  AnimationEngine   │  │    ChartManager    │
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ -container: Element│  │ -config: object    │  │ -chartInstances: Map│
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ +init(): void      │  │ +init(): void      │  │ +createChart(): Chart│
│ +updateProgress(): void│ +createAnimation(): void│ +updateChart(): void│
│ +reset(): void      │  │ +updateAnimation(): void│ +destroyCharts(): void│
└────────────────────┘  │ +createEffect(): void │  └────────────────────┘
                        │ +reset(): void      │
                        └────────────────────┘

┌────────────────────┐  ┌────────────────────┐  ┌────────────────────┐
│  AnalyzerFactory   │  │    BaseAnalyzer    │  │    APIConnector    │
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ +createAnalyzer()  │  │ -url: string       │  │ -endpoint: string  │
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ +createDOM(): Analyzer│ +analyze(): Promise │  │ +get(): Promise   │
│ +createSEO(): Analyzer│ +getResults(): object│  │ +post(): Promise  │
│ +createPerf(): Analyzer│ +isComplete(): bool │  │ +handleError(): void │
└───────────┬──────────┘ └────────┬───────────┘  └────────────────────┘
            │                     │ extends
            │ creates             │
            ▼                     ▼
┌────────────────────┐  ┌────────────────────┐  ┌────────────────────┐
│ ConcreteAnalyzers  │  │   ExportManager    │  │   UIHelper         │
├────────────────────┤  ├────────────────────┤  ├────────────────────┤
│ DOMAnalyzer        │  │ -format: string    │  │ +showLoading(): void│
│ SEOAnalyzer        │  ├────────────────────┤  │ +hideLoading(): void│
│ PerformanceAnalyzer│  │ +exportCSV(): void │  │ +showError(): void  │
└────────────────────┘  │ +exportPDF(): void │  │ +showSuccess(): void│
                        │ +print(): void      │  └────────────────────┘
                        └────────────────────┘
```

## 2. Diagramma di Stato dell'Interfaccia Utente

```
┌───────────────────────────────────────────────────────────────────────┐
│                   UI State Diagram                                     │
│                                                                        │
│  ┌─────────┐      showForm       ┌──────────┐                          │
│  │         │─────────────────────>│          │                          │
│  │ Initial │                     │  Form    │                          │
│  │         │<─────────────────────│          │                          │
│  └─────────┘      resetApp       └──────────┘                          │
│       │                               │                                │
│       │                               │ submitForm                     │
│       │                               ▼                                │
│       │                          ┌──────────┐                          │
│       │                          │          │                          │
│       │                          │ Loading  │                          │
│       │                          │          │                          │
│       │                          └──────────┘                          │
│       │                               │                                │
│       │                               │ validationComplete             │
│       │                               ▼                                │
│       │                          ┌──────────┐                          │
│       │                          │          │                          │
│       │                          │ Battle   │                          │
│       │                          │          │                          │
│       │                          └──────────┘                          │
│       │                               │                                │
│       │                               │ analysisComplete               │
│       │                               ▼                                │
│       │                          ┌──────────┐     viewCategory         │
│       │         resetApp         │          │─────────────────────────┐│
│       └─────────────────────────>│ Results  │                         ││
│                                  │          │<─────────────────────────┘│
│                                  └──────────┘     backToResults         │
│                                       │                                │
│                                       │ exportResults                  │
│                                       ▼                                │
│                                  ┌──────────┐                          │
│                                  │          │                          │
│                                  │ Export   │                          │
│                                  │          │                          │
│                                  └──────────┘                          │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 3. Diagramma delle Animazioni

```
┌───────────────────────────────────────────────────────────────────────┐
│                      Animation Flow Diagram                           │
│                                                                        │
│  ┌──────────┐     initAnimation     ┌─────────────┐                    │
│  │          │─────────────────────>│             │                    │
│  │ Idle     │                      │ Approach    │                    │
│  │          │<─────────────────────│             │                    │
│  └──────────┘        reset         └─────────────┘                    │
│                                         │                              │
│                                         │ progress > 25%               │
│                                         ▼                              │
│  ┌──────────┐                      ┌─────────────┐                    │
│  │          │        reset         │             │                    │
│  │ Victory  │<─────────────────────│ Clash       │                    │
│  │          │                      │             │                    │
│  └──────────┘                      └─────────────┘                    │
│       ▲                                 │                              │
│       │                                 │ progress > 50%               │
│       │                                 ▼                              │
│       │                            ┌─────────────┐                    │
│       │                            │             │                    │
│       │                            │ Battle      │                    │
│       │                            │             │                    │
│       │                            └─────────────┘                    │
│       │                                 │                              │
│       │                                 │ progress > 90%               │
│       └─────────────────────────────────┘                              │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 4. Diagramma di Sequenza - Processo di Analisi Frontend

```
┌─────┐         ┌─────────┐         ┌──────────┐        ┌────────────┐        ┌────────┐        ┌─────────┐
│User │         │FormUI   │         │SiteWarApp│        │BattleUI    │        │Analyzers│        │Backend  │
└──┬──┘         └────┬────┘         └────┬─────┘        └─────┬──────┘        └────┬───┘        └────┬────┘
   │                 │                    │                    │                    │                 │
   │  Submit Form    │                    │                    │                    │                 │
   │────────────────>│                    │                    │                    │                 │
   │                 │                    │                    │                    │                 │
   │                 │ Validate URLs      │                    │                    │                 │
   │                 │────────────────────│                    │                    │                 │
   │                 │                    │                    │                    │                 │
   │                 │                    │ Show Battle UI     │                    │                 │
   │                 │                    │───────────────────>│                    │                 │
   │                 │                    │                    │                    │                 │
   │                 │                    │                    │ Start Animation    │                    │                 │
   │                 │                    │                    │────────────────────│                    │                 │
   │                 │                    │                    │                    │                 │
   │                 │                    │ Create Analyzers   │                    │                 │
   │                 │                    │────────────────────────────────────────>│                 │
   │                 │                    │                    │                    │                 │
   │                 │                    │ Request Server     │                    │                 │
   │                 │                    │ Analysis           │                    │                 │
   │                 │                    │────────────────────────────────────────────────────────>│
   │                 │                    │                    │                    │                 │
   │                 │                    │<────────────────────────────────────────────────────────│
   │                 │                    │                    │                    │                 │
   │                 │                    │                    │                    │ Analysis       │
   │                 │                    │                    │                    │ Progress       │
   │                 │                    │<───────────────────────────────────────│                 │
   │                 │                    │                    │                    │                 │
   │                 │                    │ Update Progress    │                    │                 │
   │                 │                    │───────────────────>│                    │                 │
   │                 │                    │                    │                    │                 │
   │                 │                    │                    │ Update Animation   │                    │                 │
   │                 │                    │                    │────────────────────│                    │                 │
   │                 │                    │                    │                    │                 │
   │ See Animation   │                    │                    │                    │                 │
   │<────────────────────────────────────────────────────────────────────────────────────────────────│
   │                 │                    │                    │                    │                 │
   │                 │                    │ Analysis Complete  │                    │                 │
   │                 │                    │<───────────────────────────────────────────────────────>│
   │                 │                    │                    │                    │                 │
   │                 │                    │ Show Results       │                    │                 │
   │                 │                    │──────────────────────────────────────────────────────────┘
   │                 │                    │                    │                    │
   │ View Results    │                    │                    │                    │
   │<────────────────────────────────────────────────────────────────────────────────┘
   │                 │                    │                    │                    │
```

## 5. Struttura Dettagliata dei Moduli

### 5.1 Module Pattern Application Structure

```
SiteWarApp
│
├── core/
│   ├── Application.js       // Main application controller
│   ├── EventBus.js          // Custom event system
│   ├── Config.js            // Configuration parameters
│   └── Utils.js             // Utility functions
│
├── ui/
│   ├── FormUI.js            // Form handling and validation
│   ├── BattleUI.js          // Battle visualization
│   ├── ResultsUI.js         // Results display
│   ├── AnalysisUI.js        // Analysis progress display
│   └── ExportUI.js          // Export functionality
│
├── animation/
│   ├── AnimationEngine.js   // Core animation system
│   ├── BattleEffects.js     // Battle-specific effects
│   ├── ParticleSystem.js    // Particle animations
│   └── TimelineManager.js   // Animation sequences
│
├── analysis/
│   ├── AnalyzerFactory.js   // Creates analyzers
│   ├── BaseAnalyzer.js      // Base analyzer class
│   ├── analyzers/
│   │   ├── DOMAnalyzer.js   // DOM structure analysis
│   │   ├── SEOAnalyzer.js   // SEO elements analysis
│   │   ├── PerformanceAnalyzer.js // Performance metrics
│   │   └── TechAnalyzer.js  // Technology detection
│   └── ResultProcessor.js   // Processes analysis results
│
├── api/
│   ├── APIConnector.js      // API communication
│   ├── RequestManager.js    // Manages API requests
│   └── ResponseHandler.js   // Handles API responses
│
└── visualization/
    ├── ChartManager.js      // Chart creation and management
    ├── ComparisonVisualizer.js // Visual comparison tools
    └── DataFormatter.js     // Formats data for display
```

### 5.2 UI Components Structure

```
┌───────────────────────────────────────────────────────────────────────┐
│                      UI Components Hierarchy                          │
│                                                                        │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │       Main Layout       │                                          │
│  │                         │                                          │
│  └─────────────────────────┘                                          │
│              │                                                         │
│              ├─────────────────┬─────────────────┬────────────────────┤
│              │                 │                 │                    │
│  ┌───────────▼───────┐ ┌───────▼─────────┐ ┌─────▼──────────┐         │
│  │                   │ │                 │ │                │         │
│  │   Form Section    │ │  Battle Section │ │ Results Section│         │
│  │                   │ │                 │ │                │         │
│  └───────────────────┘ └─────────────────┘ └────────────────┘         │
│              │                 │                  │                    │
│              │                 │                  │                    │
│  ┌───────────▼───────┐ ┌───────▼─────────┐ ┌──────▼─────────┐ ┌───────▼───────┐
│  │                   │ │                 │ │                │ │               │
│  │ URL Input Fields  │ │ Battle Arena    │ │ Winner Display │ │ Detail Tabs   │
│  │                   │ │                 │ │                │ │               │
│  └───────────────────┘ └─────────────────┘ └────────────────┘ └───────────────┘
│                                                    │                    │
│                                                    │                    │
│                                         ┌──────────▼─────────┐ ┌────────▼──────┐
│                                         │                    │ │               │
│                                         │ Comparison Charts  │ │ Category Data │
│                                         │                    │ │               │
│                                         └────────────────────┘ └───────────────┘
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 6. Diagramma di Deployment Frontend

```
┌───────────────────────────────────────────────────────────────────────┐
│                      Frontend Deployment                              │
│                                                                        │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │     HTML Templates      │                                          │
│  │                         │                                          │
│  └────────────┬────────────┘                                          │
│               │                                                        │
│               │ includes                                              │
│               ▼                                                        │
│  ┌─────────────────────────┐        ┌────────────────────────┐        │
│  │                         │ loads  │                        │        │
│  │     Main Index          │───────>│     CSS Assets         │        │
│  │                         │        │                        │        │
│  └────────────┬────────────┘        └────────────────────────┘        │
│               │                                                        │
│               │ loads                                                 │
│               ▼                                                        │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │     JavaScript          │                                          │
│  │     Modules             │                                          │
│  │                         │                                          │
│  └────────────┬────────────┘                                          │
│               │                                                        │
│               │ loads                                                 │
│               ▼                                                        │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │     Third-party         │                                          │
│  │     Libraries           │                                          │
│  │                         │                                          │
│  └─────────────────────────┘                                          │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 7. Diagramma di Comunicazione UI-Eventi

```
┌───────────────────────────────────────────────────────────────────────┐
│                     UI Events Communication                           │
│                                                                        │
│  ┌─────────────────┐    publishes     ┌───────────────────┐           │
│  │                 │  form.submit     │                   │           │
│  │    FormUI       │────────────────>│    EventBus       │           │
│  │                 │                  │                   │           │
│  └─────────────────┘                  └─────────┬─────────┘           │
│           ▲                                     │                      │
│           │                                     │                      │
│           │ validates                           │ notifies             │
│           │                                     │                      │
│  ┌────────┴────────┐                  ┌─────────▼─────────┐           │
│  │                 │  subscribes to   │                   │           │
│  │ Validation      │  form.validated  │  SiteWarApp       │           │
│  │ Service         │<─────────────────│                   │           │
│  │                 │                  │                   │           │
│  └─────────────────┘                  └─────────┬─────────┘           │
│                                                 │                      │
│                          publishes              │                      │
│                    analysis.start               │                      │
│                     analysis.progress           │                      │
│                     analysis.complete           │                      │
│                            │                    │ subscribes to        │
│                            │                    │                      │
│  ┌─────────────────┐       │            ┌───────▼───────────┐          │
│  │                 │<──────┘            │                   │          │
│  │  BattleUI       │<───────────────────│  AnalysisUI       │          │
│  │                 │ subscribes to      │                   │          │
│  └─────────────────┘ analysis.progress  └───────────────────┘          │
│                                                                        │
│  ┌─────────────────┐                    ┌───────────────────┐          │
│  │                 │ subscribes to      │                   │          │
│  │  ResultsUI      │<───────────────────│  ExportUI         │          │
│  │                 │ export.request     │                   │          │
│  └─────────────────┘                    └───────────────────┘          │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 8. Dettaglio delle Classi Principali

### 8.1 SiteWarApplication

**Responsabilità**: Classe centrale che gestisce l'applicazione, il routing e l'orchestrazione dei componenti.

**Attributi**:
- `currentSection`: Sezione attualmente visualizzata
- `analysisResults`: Risultati dell'analisi corrente
- `siteUrls`: URLs dei siti in confronto
- `isAnalysisRunning`: Flag dello stato di analisi

**Metodi**:
- `init()`: Inizializza l'applicazione e tutti i moduli
- `showSection(sectionId)`: Cambia la sezione visualizzata
- `handleFormSubmit(event)`: Gestisce l'invio del form
- `startAnalysis(site1Url, site2Url)`: Avvia l'analisi
- `handleAnalysisComplete(results)`: Gestisce il completamento dell'analisi
- `handleAnalysisProgress(progress)`: Aggiorna lo stato di avanzamento
- `resetApp()`: Reimposta l'applicazione allo stato iniziale

### 8.2 AnimationEngine

**Responsabilità**: Gestisce tutte le animazioni della battaglia tra siti.

**Attributi**:
- `config`: Configurazione delle animazioni
- `timeline`: Timeline delle animazioni
- `particlesInstance`: Istanza del sistema di particelle
- `currentPhase`: Fase corrente della battaglia
- `siteElements`: Elementi DOM rappresentanti i siti

**Metodi**:
- `init(elements)`: Inizializza il motore di animazione
- `createBattleAnimation(site1, site2)`: Crea l'animazione della battaglia
- `updateAnimation(progress)`: Aggiorna l'animazione in base allo stato
- `createExplosion(x, y, size, color)`: Crea un effetto di esplosione
- `startRandomEffects()`: Avvia effetti casuali per la fase di battaglia
- `stopRandomEffects()`: Ferma gli effetti casuali
- `reset()`: Reimposta le animazioni allo stato iniziale

### 8.3 ResultsUI

**Responsabilità**: Visualizza e gestisce la presentazione dei risultati.

**Attributi**:
- `container`: Elemento contenitore dei risultati
- `chartInstances`: Mappe delle istanze dei grafici
- `currentView`: Vista corrente dei risultati
- `winnerBadge`: Elemento che mostra il vincitore

**Metodi**:
- `init()`: Inizializza il componente
- `displayResults(results)`: Visualizza i risultati dell'analisi
- `determineWinner(results)`: Determina il vincitore basato sui risultati
- `displaySiteStats(containerId, data, isWinner)`: Mostra le statistiche
- `displayDetailedResults(category, results)`: Mostra risultati dettagliati
- `createComparisonCharts(data)`: Crea grafici comparativi
- `formatData(data, type)`: Formatta i dati per la visualizzazione
- `reset()`: Reimposta la visualizzazione

### 8.4 AnalyzerFactory

**Responsabilità**: Factory per la creazione dei diversi analizzatori.

**Metodi**:
- `createAnalyzer(type, url)`: Crea un analizzatore del tipo specificato
- `createDOMAnalyzer(url)`: Crea un analizzatore DOM
- `createSEOAnalyzer(url)`: Crea un analizzatore SEO
- `createPerformanceAnalyzer(url)`: Crea un analizzatore di performance
- `createTechAnalyzer(url)`: Crea un analizzatore di tecnologie

### 8.5 BaseAnalyzer

**Responsabilità**: Classe base per tutti gli analizzatori.

**Attributi**:
- `url`: URL da analizzare
- `results`: Risultati dell'analisi
- `isCompleted`: Stato di completamento
- `progress`: Percentuale di completamento

**Metodi**:
- `analyze()`: Metodo astratto per eseguire l'analisi
- `getResults()`: Restituisce i risultati dell'analisi
- `isComplete()`: Verifica se l'analisi è completa
- `updateProgress(percent)`: Aggiorna lo stato di avanzamento
- `calculateScore()`: Calcola il punteggio basato sui risultati

## 9. Diagramma di Responsabilità

```
┌───────────────────────────────────────────────────────────────────────┐
│                 Frontend Component Responsibilities                    │
│                                                                        │
│  ┌─────────────────┐    User Interface    ┌─────────────────┐         │
│  │                 │<────────────────────>│                 │         │
│  │    FormUI       │                      │    ResultsUI    │         │
│  │                 │                      │                 │         │
│  └─────────────────┘                      └─────────────────┘         │
│           │                                      │                     │
│           │                                      │                     │
│           │                                      │                     │
│           ▼                                      ▼                     │
│  ┌─────────────────┐     Core Logic      ┌─────────────────┐         │
│  │                 │<────────────────────>│                 │         │
│  │ SiteWarApp      │                      │ ResultProcessor │         │
│  │                 │                      │                 │         │
│  └─────────────────┘                      └─────────────────┘         │
│           │                                      ▲                     │
│           │                                      │                     │
│           │                                      │                     │
│           ▼                                      │                     │
│  ┌─────────────────┐     Data Analysis    ┌─────────────────┐         │
│  │                 │──────────────────────>│                 │         │
│  │ AnalyzerFactory │                      │ BaseAnalyzer    │         │
│  │                 │<──────────────────────│                 │         │
│  └─────────────────┘                      └─────────────────┘         │
│           │                                      │                     │
│           │                                      │                     │
│           │                                      │                     │
│           ▼                                      ▼                     │
│  ┌─────────────────┐    Communication     ┌─────────────────┐         │
│  │                 │<────────────────────>│                 │         │
│  │ APIConnector    │                      │ EventBus        │         │
│  │                 │                      │                 │         │
│  └─────────────────┘                      └─────────────────┘         │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 10. Interfacce di Comunicazione

### 10.1 API Client Communication Interface

**RequestConfig**:
```typescript
interface RequestConfig {
  url: string;
  method: 'GET' | 'POST';
  data?: object;
  params?: object;
  headers?: Record<string, string>;
  timeout?: number;
}
```

**APIResponse**:
```typescript
interface APIResponse {
  status: 'success' | 'error';
  data?: any;
  message?: string;
  code?: number;
}
```

**AnalysisRequest**:
```typescript
interface AnalysisRequest {
  site1: string;
  site2: string;
  options?: {
    performSEO?: boolean;
    performSecurity?: boolean;
    performPerformance?: boolean;
    performTechnical?: boolean;
  };
}
```

**AnalysisResult**:
```typescript
interface AnalysisResult {
  site1: SiteAnalysis;
  site2: SiteAnalysis;
  winner: 'site1' | 'site2' | 'tie';
  comparison: {
    performance: 'site1' | 'site2' | 'tie';
    seo: 'site1' | 'site2' | 'tie';
    security: 'site1' | 'site2' | 'tie';
    technical: 'site1' | 'site2' | 'tie';
  };
}

interface SiteAnalysis {
  url: string;
  performance: PerformanceMetrics;
  seo: SEOMetrics;
  security: SecurityMetrics;
  technical: TechnicalMetrics;
}
```