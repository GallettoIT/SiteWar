# Backend Component - Diagrammi UML e Specifiche

## 1. Diagramma delle Classi Backend

```
┌────────────────────────────────────────────────────────────────────────┐
│                         APIController                                  │
├────────────────────────────────────────────────────────────────────────┤
│ -controllers: Map<string, Controller>                                  │
├────────────────────────────────────────────────────────────────────────┤
│ +__construct()                                                         │
│ +processRequest(): void                                                │
│ -sendResponse(statusCode: int, data: array): void                      │
└────────────────────────────────┬───────────────────────────────────────┘
                                 │
                                 │ manages
                                 │
                                 ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        <<interface>>                                   │
│                         Controller                                     │
├────────────────────────────────────────────────────────────────────────┤
│ +handleRequest(method: string, params: array): array                   │
└────────────────────────────────┬───────────────────────────────────────┘
                                 │
                                 │ implements
                   ┌─────────────┼─────────────┬─────────────────────────┐
                   │             │             │                         │
                   ▼             ▼             ▼                         ▼
┌──────────────────────┐ ┌───────────────┐ ┌──────────────┐ ┌─────────────────────┐
│ AnalyzeController    │ │ValidateController│ │ReportController│ │ExportController    │
├──────────────────────┤ ├───────────────┤ ├──────────────┤ ├─────────────────────┤
│ -validator: Validator│ │-validator: Validator│ │-cache: Cache │ │-exporter: Exporter │
│ -aiService: AIService│ │-aiService: AIService│ │             │ │                    │
│ -resultService: ResultService│               │ │             │ │                    │
├──────────────────────┤ ├───────────────┤ ├──────────────┤ ├─────────────────────┤
│ +__construct()       │ │+__construct() │ │+__construct()│ │+__construct()       │
│ +handleRequest(): array│ │+handleRequest(): array│ │+handleRequest(): array│ │+handleRequest(): array│
│ -analyzeSite(): array │ │-validateUrls(): bool │ │-getProgress(): int │ │-exportResults(): string│
└──────────────────────┘ └───────────────┘ └──────────────┘ └─────────────────────┘
           │
           │ uses
           ▼
┌────────────────────────────────────────────────────────────────────────┐
│                       ServiceFactory                                   │
├────────────────────────────────────────────────────────────────────────┤
│ +createAnalyzer(type: string, url: string): BaseAnalyzer               │
│ +createService(type: string): BaseService                              │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │
                                     │ creates
                       ┌─────────────┴────────────────┐
                       │                              │
                       ▼                              ▼
┌────────────────────────────────┐      ┌────────────────────────────────┐
│            BaseAnalyzer        │      │            BaseService         │
├────────────────────────────────┤      ├────────────────────────────────┤
│ #url: string                   │      │ #config: array                 │
│ #results: array                │      │ #result: mixed                 │
├────────────────────────────────┤      ├────────────────────────────────┤
│ +__construct(url: string)      │      │ +__construct(config: array)    │
│ +analyze(): array              │      │ +execute(): mixed              │
│ +getResults(): array           │      │ +getResult(): mixed            │
│ +isComplete(): bool            │      │ +hasError(): bool              │
└────────────────┬───────────────┘      └──────────────┬─────────────────┘
                 │                                      │
                 │ extends                              │ extends
        ┌────────┴───────┬───────────────┐     ┌───────┴───────┬───────────────┐
        │                │               │     │               │               │
        ▼                ▼               ▼     ▼               ▼               ▼
┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐
│SEOAnalyzer  │ │SecurityAnalyzer│ │PerformanceAnalyzer│ │ProxyService│ │AIService  │ │ResultService│
├─────────────┤ ├─────────────┤ ├─────────────┤ ├───────────┤ ├───────────┤ ├───────────┤
│-apiKeys: array│ │-apiKeys: array│ │-apiKeys: array│ │-httpClient│ │-proxyService│ │             │
├─────────────┤ ├─────────────┤ ├─────────────┤ ├───────────┤ ├───────────┤ ├───────────┤
│+analyze()    │ │+analyze()    │ │+analyze()    │ │+forwardRequest()│ │+checkRelevance()│ │+processResults()│
│-checkMeta()  │ │-checkSSL()   │ │-checkLoading()│ │-getServiceConfig()│ │             │ │-compareResults()│
│-analyzeSEO() │ │-checkHeaders()│ │-analyzePerf()│ │-implementFallback()│ │             │ │-calculateScore()│
└─────────────┘ └─────────────┘ └─────────────┘ └───────────┘ └───────────┘ └───────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                            Validator                                   │
├────────────────────────────────────────────────────────────────────────┤
│ +isValidUrl(url: string): bool                                         │
│ +sanitizeInput(data: mixed): mixed                                     │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                              Cache                                     │
├────────────────────────────────────────────────────────────────────────┤
│ -cachePath: string                                                     │
├────────────────────────────────────────────────────────────────────────┤
│ +__construct()                                                         │
│ +get(key: string): mixed                                               │
│ +set(key: string, value: mixed, ttl: int): void                        │
│ +delete(key: string): void                                             │
│ +clear(): void                                                         │
│ -getCacheFilename(key: string): string                                 │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                           HttpClient                                   │
├────────────────────────────────────────────────────────────────────────┤
│ +get(url: string, params: array = []): array                           │
│ +post(url: string, data: array = []): array                            │
│ -handleResponse(response: mixed): array                                │
│ -handleError(error: Exception): array                                  │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                          RateLimiter                                   │
├────────────────────────────────────────────────────────────────────────┤
│ -cachePath: string                                                     │
├────────────────────────────────────────────────────────────────────────┤
│ +__construct()                                                         │
│ +canMakeRequest(service: string): bool                                 │
│ +registerRequest(service: string): void                                │
│ -getServiceLimits(): array                                             │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                             Logger                                     │
├────────────────────────────────────────────────────────────────────────┤
│ -logPath: string                                                       │
│ -logLevel: int                                                         │
├────────────────────────────────────────────────────────────────────────┤
│ +__construct(logLevel: int = self::INFO)                               │
│ +debug(message: string, context: array = []): void                     │
│ +info(message: string, context: array = []): void                      │
│ +warning(message: string, context: array = []): void                   │
│ +error(message: string, context: array = []): void                     │
│ -log(level: int, message: string, context: array = []): void           │
└────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────┐
│                            Security                                    │
├────────────────────────────────────────────────────────────────────────┤
│ +sanitizeInput(data: mixed): mixed                                     │
│ +validateUrl(url: string): bool                                        │
│ +preventCsrf(): void                                                   │
│ +validateOrigin(): void                                                │
└────────────────────────────────────────────────────────────────────────┘
```

## 2. Diagramma di Stato - Richiesta API

```
┌───────────────────────────────────────────────────────────────────────┐
│                   API Request State Diagram                            │
│                                                                        │
│  ┌───────────┐     validateRequest     ┌───────────┐                   │
│  │           │───────────────────────>│           │                   │
│  │  Initial  │                         │ Validated │                   │
│  │  Request  │<───────────────────────│  Request  │                   │
│  │           │      validation        │           │                   │
│  │           │      failed            │           │                   │
│  └───────────┘                         └─────┬─────┘                   │
│       │                                      │                         │
│       │ invalid request                      │ valid request           │
│       │                                      │                         │
│       ▼                                      ▼                         │
│  ┌───────────┐                         ┌─────────────┐                 │
│  │           │                         │             │                 │
│  │ Error     │                         │ Processing  │                 │
│  │ Response  │                         │ Request     │                 │
│  │           │                         │             │                 │
│  └───────────┘                         └─────┬───────┘                 │
│                                              │                         │
│                                              │                         │
│  ┌───────────┐      error during      ┌─────┴───────┐                 │
│  │           │<─────────────────────────│             │                 │
│  │ Error     │      processing        │ Analysis    │                 │
│  │ Response  │                         │ In Progress │                 │
│  │           │                         │             │                 │
│  └───────────┘                         └─────┬───────┘                 │
│                                              │                         │
│                                              │ analysis complete       │
│                                              │                         │
│  ┌───────────┐                         ┌─────▼───────┐                 │
│  │           │                         │             │                 │
│  │ Final     │<────────────────────────│ Results     │                 │
│  │ Response  │                         │ Processing  │                 │
│  │           │                         │             │                 │
│  └───────────┘                         └─────────────┘                 │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 3. Diagramma di Sequenza - Processo di Analisi Backend

```
┌───────┐      ┌───────────┐      ┌───────────┐     ┌───────────┐     ┌────────────┐     ┌───────────┐
│Client │      │APIController│      │AnalyzeController│     │ServiceFactory│     │BaseAnalyzer │     │ExternalAPI│
└───┬───┘      └─────┬─────┘      └──────┬────┘     └──────┬────┘     └──────┬─────┘     └─────┬─────┘
    │                │                   │                 │                │                  │
    │ HTTP Request   │                   │                 │                │                  │
    │───────────────>│                   │                 │                │                  │
    │                │                   │                 │                │                  │
    │                │ Route to          │                 │                │                  │
    │                │ Controller        │                 │                │                  │
    │                │────────────────────>                │                │                  │
    │                │                   │                 │                │                  │
    │                │                   │ Create Analyzers│                │                  │
    │                │                   │────────────────>│                │                  │
    │                │                   │                 │                │                  │
    │                │                   │                 │ Create Analyzer│                  │
    │                │                   │                 │───────────────>│                  │
    │                │                   │                 │                │                  │
    │                │                   │                 │ Return         │                  │
    │                │                   │                 │ Analyzer       │                  │
    │                │                   │                 │<───────────────│                  │
    │                │                   │                 │                │                  │
    │                │                   │ Return Analyzers│                │                  │
    │                │                   │<────────────────│                │                  │
    │                │                   │                 │                │                  │
    │                │                   │                 │                │                  │
    │                │                   │ Perform Analysis│                │                  │
    │                │                   │───────────────────────────────────>                │
    │                │                   │                 │                │                  │
    │                │                   │                 │                │ API Request     │
    │                │                   │                 │                │────────────────>│
    │                │                   │                 │                │                  │
    │                │                   │                 │                │ API Response    │
    │                │                   │                 │                │<────────────────│
    │                │                   │                 │                │                  │
    │                │                   │                 │                │                  │
    │                │                   │ Return Results  │                │                  │
    │                │                   │<──────────────────────────────────                  │
    │                │                   │                 │                │                  │
    │                │                   │ Process Results │                │                  │
    │                │                   │────────────────>│                │                  │
    │                │                   │                 │                │                  │
    │                │ Return Response   │                 │                │                  │
    │                │<─────────────────────────────────────────────────────────────────────────
    │                │                   │                 │                │                  │
    │ HTTP Response  │                   │                 │                │                  │
    │<───────────────│                   │                 │                │                  │
    │                │                   │                 │                │                  │
```

## 4. Diagramma di Collaborazione - API Integration

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│                    API Integration Collaboration                         │
│                                                                         │
│                        1. request API                                   │
│       ┌──────────┐ ─────────────────────────────────> ┌──────────────┐ │
│       │          │                                    │              │ │
│       │ Analyzer │                                    │ ProxyService │ │
│       │          │ <─────────────────────────────────┐│              │ │
│       └──────────┘           2. return result        └──────────────┘ │
│            │                                                │          │
│            │                                                │          │
│ 4. process │                                                │          │
│    results │                                                │ 3. proxy │
│            │                                                │    request│
│            ▼                                                │          │
│       ┌──────────┐                                          │          │
│       │          │                                          ▼          │
│       │ Result   │                                    ┌──────────────┐ │
│       │ Processor│                                    │              │ │
│       │          │                                    │ External API │ │
│       └──────────┘                                    │              │ │
│            │                                          └──────────────┘ │
│            │                                                           │
│    5. final│                                                           │
│      result│                                                           │
│            ▼                                                           │
│       ┌──────────┐                                                     │
│       │          │                                                     │
│       │ API      │                                                     │
│       │ Response │                                                     │
│       │          │                                                     │
│       └──────────┘                                                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## 5. Struttura Dettagliata dei Moduli Backend

### 5.1 Struttura dei File Server

```
server/
│
├── api/
│   ├── index.php                 # Entry point per tutte le richieste API
│   ├── controllers/              # Controller per diversi endpoint
│   │   ├── AnalyzeController.php
│   │   ├── ValidateController.php
│   │   └── ReportController.php
│   └── config/                   # Configurazioni API
│       ├── api_keys.php          # Chiavi API (protette)
│       └── services.php          # Configurazione servizi
│
├── core/                         # Core del sistema
│   ├── APIController.php         # Controller API principale
│   ├── Controller.php            # Interfaccia controller
│   ├── ServiceFactory.php        # Factory per servizi e analizzatori
│   └── ConfigManager.php         # Gestione configurazione
│
├── services/                     # Servizi business logic
│   ├── analyzers/                # Analizzatori specifici
│   │   ├── BaseAnalyzer.php      # Classe base analizzatore
│   │   ├── SEOAnalyzer.php
│   │   ├── SecurityAnalyzer.php
│   │   ├── PerformanceAnalyzer.php
│   │   └── TechnologyAnalyzer.php
│   ├── AIService.php             # Servizio AI
│   ├── ProxyService.php          # Proxy API
│   └── ResultService.php         # Elaborazione risultati
│
├── utils/                        # Utility
│   ├── Cache.php                 # Sistema di cache
│   ├── HttpClient.php            # Client HTTP
│   ├── Logger.php                # Sistema di logging
│   ├── RateLimiter.php           # Limitatore di frequenza
│   ├── Validator.php             # Validazione input
│   └── Security.php              # Funzioni sicurezza
│
└── cache/                        # Directory per file di cache
    ├── data/                     # Cache dei dati
    └── ratelimit/                # Cache per rate limiting
```

### 5.2 Gerarchia delle Classi e Interfacce

```
┌───────────────────────────────────────────────────────────────────────┐
│                       Backend Class Hierarchy                          │
│                                                                        │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │    <<Interface>>        │                                          │
│  │      Controller         │                                          │
│  │                         │                                          │
│  └─────────────┬───────────┘                                          │
│                │                                                       │
│                │ implements                                           │
│                │                                                       │
│  ┌─────────────▼───────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │    AnalyzeController    │      │     ValidateController  │         │
│  │                         │      │                         │         │
│  └─────────────────────────┘      └─────────────────────────┘         │
│                                                                        │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │     BaseAnalyzer        │      │      BaseService        │         │
│  │       (abstract)        │      │        (abstract)       │         │
│  │                         │      │                         │         │
│  └─────────────┬───────────┘      └─────────────┬───────────┘         │
│                │                                 │                     │
│                │ extends                         │ extends            │
│                │                                 │                     │
│  ┌─────────────▼───────────┐      ┌─────────────▼───────────┐         │
│  │                         │      │                         │         │
│  │     SEOAnalyzer         │      │      ProxyService       │         │
│  │                         │      │                         │         │
│  └─────────────────────────┘      └─────────────────────────┘         │
│                                                                        │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │     SecurityAnalyzer    │      │      AIService          │         │
│  │                         │      │                         │         │
│  └─────────────────────────┘      └─────────────────────────┘         │
│                                                                        │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │     PerformanceAnalyzer │      │      ResultService      │         │
│  │                         │      │                         │         │
│  └─────────────────────────┘      └─────────────────────────┘         │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 6. Diagramma dei Componenti di Sistema

```
┌───────────────────────────────────────────────────────────────────────┐
│                    Backend System Components                          │
│                                                                        │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │    API Interface        │      │      Core System        │         │
│  │                         │      │                         │         │
│  └─────────────┬───────────┘      └─────────────┬───────────┘         │
│                │                                 │                     │
│                │                                 │                     │
│                ▼                                 ▼                     │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │    Controller Layer     │      │     Service Layer       │         │
│  │                         │      │                         │         │
│  └─────────────┬───────────┘      └─────────────┬───────────┘         │
│                │                                 │                     │
│                │                                 │                     │
│                ▼                                 ▼                     │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │    Analysis Engine      │      │     External API        │         │
│  │                         │      │     Integration         │         │
│  └─────────────┬───────────┘      └─────────────┬───────────┘         │
│                │                                 │                     │
│                │                                 │                     │
│                ▼                                 ▼                     │
│  ┌─────────────────────────┐      ┌─────────────────────────┐         │
│  │                         │      │                         │         │
│  │    Results Processing   │      │     Infrastructure      │         │
│  │                         │      │     (Cache, Security)   │         │
│  └─────────────────────────┘      └─────────────────────────┘         │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 7. Diagramma di Attività - Analisi Backend

```
┌──────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│                      Backend Analysis Process                            │
│                                                                          │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ Start      │                                                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ Validate   │                                                          │
│  │ Request    │                                                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │ No                                                       │
│  │ Valid?     ├───────────────────────────────┐                          │
│  │            │                               │                          │
│  └──────┬─────┘                               │                          │
│         │ Yes                                 │                          │
│         ▼                                     ▼                          │
│  ┌────────────┐                        ┌────────────┐                    │
│  │            │                        │            │                    │
│  │ Check      │                        │ Return     │                    │
│  │ Relevance  │                        │ Error      │                    │
│  │            │                        │            │                    │
│  └──────┬─────┘                        └────────────┘                    │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │ No                                                       │
│  │ Relevant?  ├───────────────────────────────┐                          │
│  │            │                               │                          │
│  └──────┬─────┘                               │                          │
│         │ Yes                                 │                          │
│         ▼                                     ▼                          │
│  ┌────────────┐                        ┌────────────┐                    │
│  │            │                        │            │                    │
│  │ Check      │                        │ Return     │                    │
│  │ Cache      │                        │ Warning    │                    │
│  │            │                        │            │                    │
│  └──────┬─────┘                        └──────┬─────┘                    │
│         │                                     │                          │
│         ▼                                     │                          │
│  ┌────────────┐                               │                          │
│  │            │ Yes                           │                          │
│  │ Cached?    ├───────────────────┐           │                          │
│  │            │                   │           │                          │
│  └──────┬─────┘                   │           │                          │
│         │ No                      │           │                          │
│         ▼                         ▼           │                          │
│  ┌────────────┐             ┌────────────┐    │                          │
│  │            │             │            │    │                          │
│  │ Create     │             │ Get Cached │    │                          │
│  │ Analyzers  │             │ Results    │    │                          │
│  │            │             │            │    │                          │
│  └──────┬─────┘             └──────┬─────┘    │                          │
│         │                          │          │                          │
│         ▼                          │          │                          │
│  ┌────────────┐                    │          │                          │
│  │            │                    │          │                          │
│  │ Perform    │                    │          │                          │
│  │ Analysis   │                    │          │                          │
│  │            │                    │          │                          │
│  └──────┬─────┘                    │          │                          │
│         │                          │          │                          │
│         ▼                          │          │                          │
│  ┌────────────┐                    │          │                          │
│  │            │                    │          │                          │
│  │ Process    │<───────────────────┘          │                          │
│  │ Results    │<──────────────────────────────┘                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ Compare    │                                                          │
│  │ Sites      │                                                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ Determine  │                                                          │
│  │ Winner     │                                                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ Cache      │                                                          │
│  │ Results    │                                                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ Return     │                                                          │
│  │ Response   │                                                          │
│  │            │                                                          │
│  └──────┬─────┘                                                          │
│         │                                                                │
│         ▼                                                                │
│  ┌────────────┐                                                          │
│  │            │                                                          │
│  │ End        │                                                          │
│  │            │                                                          │
│  └────────────┘                                                          │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

## 8. Dettaglio delle Classi Principali

### 8.1 APIController

**Responsabilità**: Classe principale che gestisce tutte le richieste API, il routing e le risposte.

**Attributi**:
- `controllers`: Array associativo di controller disponibili

**Metodi**:
- `__construct()`: Inizializza il controller e configura gli header
- `processRequest()`: Elabora la richiesta HTTP e la indirizza al controller appropriato
- `sendResponse(statusCode, data)`: Invia la risposta HTTP con lo stato e i dati specificati

### 8.2 AnalyzeController

**Responsabilità**: Gestisce le richieste di analisi dei siti web.

**Attributi**:
- `validator`: Istanza del validatore
- `aiService`: Istanza del servizio AI
- `resultService`: Istanza del servizio di elaborazione risultati

**Metodi**:
- `__construct()`: Inizializza il controller e i servizi necessari
- `handleRequest(method, params)`: Gestisce la richiesta di analisi
- `analyzeSite(url)`: Esegue l'analisi completa di un sito

### 8.3 BaseAnalyzer

**Responsabilità**: Classe base astratta per tutti gli analizzatori specifici.

**Attributi**:
- `url`: URL del sito da analizzare
- `results`: Risultati dell'analisi
- `isCompleted`: Flag di completamento

**Metodi**:
- `__construct(url)`: Inizializza l'analizzatore con l'URL
- `analyze()`: Metodo astratto da implementare nelle sottoclassi
- `getResults()`: Restituisce i risultati dell'analisi
- `isComplete()`: Verifica se l'analisi è completa

### 8.4 SecurityAnalyzer

**Responsabilità**: Analizza gli aspetti di sicurezza di un sito web.

**Attributi**:
- `url`: URL del sito da analizzare
- `httpClient`: Client HTTP per le richieste
- `cache`: Sistema di cache per risultati

**Metodi**:
- `__construct(url)`: Inizializza l'analizzatore
- `analyze()`: Esegue l'analisi di sicurezza completa
- `checkSSL()`: Verifica il certificato SSL
- `checkSecurityHeaders()`: Analizza gli header di sicurezza
- `checkVulnerabilities()`: Verifica vulnerabilità note
- `calculateScore()`: Calcola il punteggio di sicurezza

### 8.5 ProxyService

**Responsabilità**: Gestisce le comunicazioni con API esterne proteggendo le chiavi API.

**Attributi**:
- `httpClient`: Client HTTP per le richieste
- `cache`: Sistema di cache per richieste API
- `rateLimiter`: Limitatore di frequenza

**Metodi**:
- `__construct()`: Inizializza il servizio
- `forwardRequest(service, endpoint, params)`: Inoltra una richiesta all'API esterna
- `getServiceConfig(service)`: Ottiene la configurazione del servizio
- `implementFallback(service, endpoint, params)`: Implementa strategie di fallback

### 8.6 ResultService

**Responsabilità**: Elabora i risultati delle analisi e determina il vincitore.

**Metodi**:
- `processResults(site1Url, site2Url, site1Results, site2Results)`: Elabora i risultati completi
- `compareResults(site1Results, site2Results)`: Confronta i risultati dei due siti
- `calculateFinalScore(siteResults)`: Calcola il punteggio finale pesato

### 8.7 Cache

**Responsabilità**: Gestisce il caching dei dati per migliorare le prestazioni.

**Attributi**:
- `cachePath`: Percorso della directory di cache

**Metodi**:
- `__construct()`: Inizializza il sistema di cache
- `get(key)`: Ottiene un valore dalla cache
- `set(key, value, ttl)`: Memorizza un valore in cache con tempo di vita
- `delete(key)`: Elimina una chiave dalla cache
- `clear()`: Pulisce tutta la cache
- `getCacheFilename(key)`: Genera il nome file sicuro per la cache

## 9. Diagramma di Accesso ai Dati

```
┌───────────────────────────────────────────────────────────────────────┐
│                      Data Access Patterns                              │
│                                                                        │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │    API Request          │                                          │
│  │                         │                                          │
│  └─────────────┬───────────┘                                          │
│                │                                                       │
│                │                                                       │
│                ▼                                                       │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │    Cache Check          │                                          │
│  │                         │                                          │
│  └─────────────┬───────────┘                                          │
│                │                                                       │
│                │                                                       │
│          ┌─────┴─────┐                                                │
│          │           │                                                │
│          ▼           ▼                                                │
│  ┌───────────┐ ┌─────────────┐                                        │
│  │           │ │             │                                        │
│  │ Cache Hit │ │ Cache Miss  │                                        │
│  │           │ │             │                                        │
│  └─────┬─────┘ └──────┬──────┘                                        │
│        │              │                                               │
│        │              ▼                                               │
│        │       ┌─────────────┐                                        │
│        │       │             │                                        │
│        │       │ External    │                                        │
│        │       │ API Request │                                        │
│        │       │             │                                        │
│        │       └──────┬──────┘                                        │
│        │              │                                               │
│        │              ▼                                               │
│        │       ┌─────────────┐                                        │
│        │       │             │                                        │
│        │       │ Store in    │                                        │
│        │       │ Cache       │                                        │
│        │       │             │                                        │
│        │       └──────┬──────┘                                        │
│        │              │                                               │
│        └──────────────┘                                               │
│                │                                                       │
│                ▼                                                       │
│  ┌─────────────────────────┐                                          │
│  │                         │                                          │
│  │    Return Data          │                                          │
│  │                         │                                          │
│  └─────────────────────────┘                                          │
│                                                                        │
└───────────────────────────────────────────────────────────────────────┘
```

## 10. Interfacce API

### 10.1 API Endpoints

| Endpoint | Metodo | Descrizione | Parametri |
|----------|--------|-------------|-----------|
| /api/analyze | POST | Esegue l'analisi completa | site1, site2 |
| /api/validate | POST | Valida gli URL e la pertinenza | site1, site2 |
| /api/progress | GET | Controlla lo stato di avanzamento | session_id |
| /api/export | GET | Esporta i risultati in diversi formati | session_id, format |

### 10.2 Formato Richiesta API Analyze

```json
{
  "site1": "https://example1.com",
  "site2": "https://example2.com",
  "options": {
    "performSEO": true,
    "performSecurity": true,
    "performPerformance": true,
    "performTechnical": true
  }
}
```

### 10.3 Formato Risposta API Analyze

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
      "url": "https://example2.com",
      "performance": {
        "score": 75,
        "metrics": {
          "fcp": 1800,
          "lcp": 3200,
          "tti": 4100,
          "cls": 0.08,
          "totalSize": 2200000
        }
      },
      "seo": {
        "score": 82,
        "metrics": {
          "title": "Excellent",
          "meta": "Good",
          "headings": "Average",
          "images": "Good",
          "links": "Average"
        }
      },
      "security": {
        "score": 78,
        "metrics": {
          "ssl": "A",
          "headers": "Average",
          "vulnerabilities": 1,
          "outdated": false
        }
      },
      "technical": {
        "score": 79,
        "metrics": {
          "html": "Valid",
          "css": "Valid",
          "javascript": "Modern",
          "responsive": true,
          "technologies": ["HTML5", "CSS3", "JavaScript", "Bootstrap"]
        }
      }
    },
    "winner": "site1",
    "comparison": {
      "performance": "site1",
      "seo": "site2",
      "security": "site1",
      "technical": "site1"
    }
  }
}
```