# Architettura di Sistema - Site War

## 1. Visione d'Insieme dell'Architettura

Site War implementa un'architettura client-server con elaborazione distribuita, dove la maggior parte dell'analisi viene eseguita lato client per ottimizzare le prestazioni e ridurre il carico sul server.

### 1.1 Diagramma Architetturale

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CLIENT (BROWSER)                            │
│                                                                     │
│  ┌───────────────────┐      ┌───────────────────┐                   │
│  │                   │      │                   │                   │
│  │  PRESENTATION     │      │  CLIENT ANALYSIS  │                   │
│  │  LAYER            │<────>│  ENGINE           │                   │
│  │                   │      │                   │                   │
│  └───────┬───────────┘      └─────────┬─────────┘                   │
│          │                            │                             │
│          │                            │                             │
│          │                            │                             │
│  ┌───────▼───────────┐      ┌─────────▼─────────┐                   │
│  │                   │      │                   │                   │
│  │  UI COMPONENTS    │      │  DATA PROCESSING  │                   │
│  │                   │      │                   │                   │
│  └───────┬───────────┘      └─────────┬─────────┘                   │
│          │                            │                             │
└──────────┼────────────────────────────┼─────────────────────────────┘
           │                            │
           │                            │
┌──────────┼────────────────────────────┼─────────────────────────────┐
│          │                            │                             │
│  ┌───────▼───────────┐      ┌─────────▼─────────┐                   │
│  │                   │      │                   │                   │
│  │  API CONTROLLER   │<────>│  ANALYSIS         │                   │
│  │                   │      │  ORCHESTRATOR     │                   │
│  └───────┬───────────┘      └─────────┬─────────┘                   │
│          │                            │                             │
│          │                            │                             │
│  ┌───────▼───────────┐      ┌─────────▼─────────┐                   │
│  │                   │      │                   │                   │
│  │  SERVICE LAYER    │<────>│  RESULTS          │                   │
│  │                   │      │  PROCESSOR        │                   │
│  └───────┬───────────┘      └─────────┬─────────┘                   │
│          │                            │                             │
│          │                            │                             │
│  ┌───────▼───────────┐                │                             │
│  │                   │                │                             │
│  │  EXTERNAL API     │                │                             │
│  │  INTEGRATION      │                │                             │
│  │                   │                │                             │
│  └───────────────────┘                │                             │
│                                       │                             │
│                       SERVER (PHP)    │                             │
└───────────────────────────────────────┼─────────────────────────────┘
                                        │
                                        │
┌───────────────────────────────────────▼─────────────────────────────┐
│                                                                     │
│                      EXTERNAL SERVICES                              │
│                                                                     │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐             │
│  │               │ │               │ │               │             │
│  │ PageSpeed API │ │ WHOIS API     │ │ Security      │             │
│  │               │ │               │ │ Headers API   │             │
│  └───────────────┘ └───────────────┘ └───────────────┘             │
│                                                                     │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐             │
│  │               │ │               │ │               │             │
│  │ Moz API       │ │ W3C Validator │ │ OpenAI API    │             │
│  │               │ │               │ │               │             │
│  └───────────────┘ └───────────────┘ └───────────────┘             │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 1.2 Modello di Interazione

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│          │    │          │    │          │    │          │    │          │
│ Browser  │───>│ Frontend │───>│ Backend  │───>│ External │───>│ Results  │
│ Client   │<───│ Analysis │<───│ Analysis │<───│ APIs     │<───│ Processing│
│          │    │          │    │          │    │          │    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘    └──────────┘
```

## 2. Macrocomponenti del Sistema

### 2.1 Diagramma dei Componenti

```
┌───────────────────────────────────────────────────────────────────────────┐
│                                                                           │
│                             Site War System                               │
│                                                                           │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐         │
│  │               │      │               │      │               │         │
│  │  Frontend     │◄────►│  Backend      │◄────►│  Analysis     │         │
│  │  Component    │      │  Component    │      │  Component    │         │
│  │               │      │               │      │               │         │
│  └───────┬───────┘      └───────┬───────┘      └───────┬───────┘         │
│          │                      │                      │                 │
│          │                      │                      │                 │
│          ▼                      ▼                      ▼                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐         │
│  │               │      │               │      │               │         │
│  │  UI Layer     │      │  API Layer    │      │  Data Layer   │         │
│  │  Component    │      │  Component    │      │  Component    │         │
│  │               │      │               │      │               │         │
│  └───────────────┘      └───────────────┘      └───────────────┘         │
│                                                                           │
└───────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Descrizione dei Macrocomponenti

#### 2.2.1 Frontend Component
Gestisce l'interfaccia utente, le animazioni e l'interazione con l'utente. Implementa l'analisi lato client e la visualizzazione dei risultati.

#### 2.2.2 Backend Component
Gestisce le richieste API, coordina le analisi avanzate e integra i servizi esterni. Include la logica di business lato server.

#### 2.2.3 Analysis Component
Implementa gli algoritmi di analisi, confronto e punteggio. Include componenti sia lato client che lato server.

#### 2.2.4 UI Layer Component
Gestisce specificamente la rappresentazione visiva dell'interfaccia utente e le animazioni della "guerra" tra siti.

#### 2.2.5 API Layer Component
Gestisce l'interfaccia tra frontend e backend, l'autenticazione e la validazione delle richieste.

#### 2.2.6 Data Layer Component
Gestisce l'elaborazione, l'aggregazione e la formattazione dei dati di analisi.

## 3. Pattern Architetturali

### 3.1 Pattern Utilizzati

```
┌───────────────────────────────────────────────────┐
│                                                   │
│               System Architecture                 │
│                                                   │
│  ┌───────────────┐        ┌───────────────┐      │
│  │               │        │               │      │
│  │ Module Pattern│        │ Observer      │      │
│  │               │        │ Pattern       │      │
│  └───────────────┘        └───────────────┘      │
│                                                   │
│  ┌───────────────┐        ┌───────────────┐      │
│  │               │        │               │      │
│  │ Factory       │        │ Facade        │      │
│  │ Pattern       │        │ Pattern       │      │
│  │               │        │               │      │
│  └───────────────┘        └───────────────┘      │
│                                                   │
│  ┌───────────────┐        ┌───────────────┐      │
│  │               │        │               │      │
│  │ Strategy      │        │ Adapter       │      │
│  │ Pattern       │        │ Pattern       │      │
│  │               │        │               │      │
│  └───────────────┘        └───────────────┘      │
│                                                   │
│  ┌───────────────┐        ┌───────────────┐      │
│  │               │        │               │      │
│  │ MVC           │        │ Proxy         │      │
│  │ Pattern       │        │ Pattern       │      │
│  │               │        │               │      │
│  └───────────────┘        └───────────────┘      │
│                                                   │
└───────────────────────────────────────────────────┘
```

### 3.2 Applicazione dei Pattern

| Pattern | Applicazione in Site War |
|---------|--------------------------|
| Module | Organizzazione del codice JavaScript in moduli autonomi |
| Observer | Notifica delle analisi completate e aggiornamenti UI |
| Factory | Creazione di analizzatori specifici per ciascun tipo di test |
| Facade | Interfaccia semplificata per il complesso sistema di analisi |
| Strategy | Implementazione di diverse strategie di analisi e confronto |
| Adapter | Integrazione uniforme con diverse API esterne |
| MVC | Separazione tra dati, logica e presentazione |
| Proxy | Protezione delle API key e intermediazione con API esterne |

## 4. Flusso dei Dati

### 4.1 Diagramma di Flusso dei Dati

```
┌──────────┐     ┌──────────┐     ┌───────────┐     ┌────────────┐
│          │     │          │     │           │     │            │
│  User    │────>│  URL     │────>│ Validation│────>│ Client-side│
│  Input   │     │  Input   │     │ Service   │     │ Analysis   │
│          │     │          │     │           │     │            │
└──────────┘     └──────────┘     └───────────┘     └──────┬─────┘
                                                          │
                                                          │
                                                          ▼
┌───────────┐     ┌──────────┐     ┌───────────┐     ┌────────────┐
│           │     │          │     │           │     │            │
│  Results  │<────│ Result   │<────│ Server-   │<────│ API        │
│  Display  │     │ Process  │     │ side      │     │ Integration│
│           │     │          │     │ Analysis  │     │            │
└───────────┘     └──────────┘     └───────────┘     └────────────┘
```

### 4.2 Eventi del Sistema

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│ User Initiated  │────>│ System Processing│────>│ Result Events   │
│ Events          │     │ Events          │     │                 │
│                 │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
       │                        │                        │
       ▼                        ▼                        ▼
┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│ URL Submit  │         │ Analysis    │         │ Display     │
│ Form Reset  │         │ Validation  │         │ Results     │
│ Export      │         │ API Call    │         │ Show Winner │
│ New Battle  │         │ Progress    │         │ Animation   │
└─────────────┘         └─────────────┘         └─────────────┘
```

## 5. Integrazione con Servizi Esterni

### 5.1 Diagramma di Integrazione

```
┌───────────────────────────────────────────────────────────────────┐
│                          Site War System                          │
│                                                                   │
│                   ┌───────────────────────────────────┐           │
│                   │                                   │           │
│                   │        Proxy Service Layer        │           │
│                   │                                   │           │
│                   └───┬─────────┬─────────┬─────────┬─┘           │
│                       │         │         │         │             │
└───────────────────────┼─────────┼─────────┼─────────┼─────────────┘
                        │         │         │         │
                        │         │         │         │
                        ▼         ▼         ▼         ▼
          ┌─────────────────┐ ┌─────────┐ ┌───────┐ ┌────────────┐
          │                 │ │         │ │       │ │            │
          │  Performance    │ │  SEO    │ │ Security│ │ Technology │
          │  Services       │ │ Services│ │Services│ │ Services   │
          │                 │ │         │ │       │ │            │
          └─────────────────┘ └─────────┘ └───────┘ └────────────┘
                   │              │           │           │
                   │              │           │           │
                   ▼              ▼           ▼           ▼
          ┌─────────────────┐ ┌─────────┐ ┌───────┐ ┌────────────┐
          │                 │ │         │ │       │ │            │
          │  PageSpeed API  │ │ Moz API │ │Security│ │ Wappalyzer │
          │  Google API     │ │         │ │Headers│ │            │
          │                 │ │         │ │       │ │            │
          └─────────────────┘ └─────────┘ └───────┘ └────────────┘
```

### 5.2 Strategie di Resilienza

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                    API Integration Strategy                     │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Primary API   │─────>│ API Response  │─────>│ Cache Layer   ││
│  │ Call          │      │ Handler       │      │               ││
│  │               │      │               │      │               ││
│  └───────┬───────┘      └───────────────┘      └───────────────┘│
│          │                                                      │
│          │ Failure                                              │
│          ▼                                                      │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Secondary API │─────>│ Alternative   │─────>│ Degraded      ││
│  │ Call          │      │ Processing    │      │ Experience    ││
│  │               │      │               │      │               ││
│  └───────┬───────┘      └───────────────┘      └───────────────┘│
│          │                                                      │
│          │ Failure                                              │
│          ▼                                                      │
│  ┌───────────────┐      ┌───────────────┐                      │
│  │               │      │               │                      │
│  │ Client-side   │─────>│ Fallback      │                      │
│  │ Alternative   │      │ Results       │                      │
│  │               │      │               │                      │
│  └───────────────┘      └───────────────┘                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## 6. Scalabilità e Performance

### 6.1 Strategie di Ottimizzazione

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                   Performance Optimization                      │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Client-side   │      │ API           │      │ Intelligent   ││
│  │ Processing    │      │ Batching      │      │ Caching       ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Parallel      │      │ Lazy Loading  │      │ Resource      ││
│  │ Processing    │      │ UI Components │      │ Optimization  ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Distribuzione del Carico

```
┌─────────────────────┐
│                     │
│  Workload Division  │
│                     │
└─────────┬───────────┘
          │
          │
┌─────────▼───────────┬────────────────────┬─────────────────────┐
│                     │                    │                     │
│  Client-side (65%)  │  Server-side (25%) │  External APIs (10%)│
│                     │                    │                     │
└─────────────────────┴────────────────────┴─────────────────────┘
          │                    │                     │
          │                    │                     │
┌─────────▼───────┐   ┌────────▼────────┐   ┌────────▼────────┐
│ DOM Analysis    │   │ Security Checks │   │ SEO Advanced    │
│ Basic SEO       │   │ Advanced Tech   │   │ WHOIS Data      │
│ Performance     │   │ Detection       │   │ SSL Certificate │
│ Visual Metrics  │   │ Result Process  │   │ External Valid. │
└─────────────────┘   └─────────────────┘   └─────────────────┘
```

## 7. Sicurezza dell'Architettura

### 7.1 Modello di Sicurezza

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                      Security Architecture                      │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Client-side   │      │ API Layer     │      │ Server-side   ││
│  │ Security      │      │ Security      │      │ Security      ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
          │                    │                     │
          │                    │                     │
┌─────────▼───────┐   ┌────────▼────────┐   ┌────────▼────────┐
│ Input Valid.    │   │ Request Valid.  │   │ API Key Protect │
│ XSS Prevention  │   │ CSRF Protection │   │ Input Sanitiz.  │
│ CSP Implement.  │   │ Rate Limiting   │   │ Error Handling  │
│ Obfuscation     │   │ Authentication  │   │ HTTPS Enforce.  │
└─────────────────┘   └─────────────────┘   └─────────────────┘
```

### 7.2 Protezione Dati

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                        Data Protection                          │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Secure        │─────>│ Proxy         │─────>│ API Key       ││
│  │ Communication │      │ Service       │      │ Management    ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## 8. Risposta agli Errori

### 8.1 Strategia di Gestione Errori

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                      Error Handling Strategy                    │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Client-side   │─────>│ Error         │─────>│ User          ││
│  │ Detection     │      │ Classification│      │ Feedback      ││
│  │               │      │               │      │               ││
│  └───────┬───────┘      └───────────────┘      └───────────────┘│
│          │                                                      │
│          │                                                      │
│          ▼                                                      │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Server-side   │─────>│ Error         │─────>│ Fallback      ││
│  │ Detection     │      │ Logging       │      │ Mechanism     ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 8.2 Tipi di Errore Gestiti

| Tipo di Errore | Strategia di Gestione |
|----------------|------------------------|
| URL non valido | Validazione client-side con feedback immediato |
| API non disponibile | Utilizzo di cache o alternativa client-side |
| Timeout analisi | Risultati parziali con notifica all'utente |
| Errore JS client | Catch globale con logging e riavvio modulo |
| Errore server | Risposta di errore con suggerimento alternativo |
| Accesso negato API | Fallback a funzionalità limitate senza API |

## 9. Deployment e Ambiente

### 9.1 Architettura di Deployment

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                      Deployment Architecture                    │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Development   │─────>│ Testing       │─────>│ Production    ││
│  │ Environment   │      │ Environment   │      │ Environment   ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
          │                    │                     │
          │                    │                     │
┌─────────▼───────┐   ┌────────▼────────┐   ┌────────▼────────┐
│ Local Dev       │   │ Integration     │   │ Production      │
│ Docker Env      │   │ Automated Tests │   │ Hosting         │
│ Mock APIs       │   │ Performance Test│   │ CDN             │
│ Live Reload     │   │ Security Scan   │   │ Monitoring      │
└─────────────────┘   └─────────────────┘   └─────────────────┘
```

### 9.2 Stack Tecnologico

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                      Technology Stack                           │
│                                                                 │
│  ┌───────────────┐      ┌───────────────┐      ┌───────────────┐│
│  │               │      │               │      │               ││
│  │ Frontend      │      │ Backend       │      │ DevOps        ││
│  │ Technologies  │      │ Technologies  │      │ Technologies  ││
│  │               │      │               │      │               ││
│  └───────────────┘      └───────────────┘      └───────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
          │                    │                     │
          │                    │                     │
┌─────────▼───────┐   ┌────────▼────────┐   ┌────────▼────────┐
│ HTML5/CSS3      │   │ PHP             │   │ Git              │
│ JavaScript      │   │ JSON            │   │ Docker           │
│ jQuery          │   │ REST API        │   │ Jenkins          │
│ Bootstrap 5     │   │ File Caching    │   │ Lighthouse CLI   │
│ Chart.js        │   │                 │   │ OWASP ZAP        │
│ Anime.js        │   │                 │   │                  │
└─────────────────┘   └─────────────────┘   └─────────────────┘
```