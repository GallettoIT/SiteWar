# Modello dei Dati - Diagrammi UML e Specifiche

## 1. Diagramma delle Classi - Modello dei Dati

```
┌─────────────────────────────────────────────────────────────────────┐
│                           AnalysisResult                            │
├─────────────────────────────────────────────────────────────────────┤
│ +site1: SiteAnalysis                                                │
│ +site2: SiteAnalysis                                                │
│ +winner: string                                                     │
│ +comparison: ComparisonResult                                       │
│ +timestamp: number                                                  │
├─────────────────────────────────────────────────────────────────────┤
│ +determineWinner(): string                                          │
│ +getWinningCategories(site: string): string[]                       │
│ +toJSON(): object                                                   │
└───────────────────────────────────────────────────────────────────┬─┘
                                                                     │
                                                                     │
┌─────────────────────────────────────────────────────────────────────┐
│                           SiteAnalysis                              │
├─────────────────────────────────────────────────────────────────────┤
│ +url: string                                                        │
│ +performance: PerformanceMetrics                                    │
│ +seo: SEOMetrics                                                    │
│ +security: SecurityMetrics                                          │
│ +technical: TechnicalMetrics                                        │
│ +finalScore: number                                                 │
├─────────────────────────────────────────────────────────────────────┤
│ +calculateFinalScore(): number                                      │
│ +getHighestScoringCategory(): string                                │
│ +getLowestScoringCategory(): string                                 │
└───────────────────────────────────────────────────────────────────┬─┘
                                                                     │
                                                                     │
┌─────────────────────────────────────────────────────────────────────┐
│                         ComparisonResult                            │
├─────────────────────────────────────────────────────────────────────┤
│ +performance: string                                                │
│ +seo: string                                                        │
│ +security: string                                                   │
│ +technical: string                                                  │
│ +overallScore: string                                               │
│ +scoreGap: number                                                   │
├─────────────────────────────────────────────────────────────────────┤
│ +getWinningCategories(site: string): string[]                       │
│ +isCloseDuel(): boolean                                             │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                          BaseMetrics                                │
├─────────────────────────────────────────────────────────────────────┤
│ +score: number                                                      │
│ +metrics: object                                                    │
│ +details: object                                                    │
├─────────────────────────────────────────────────────────────────────┤
│ +getScore(): number                                                 │
│ +getMetrics(): object                                               │
│ +getDetails(): object                                               │
└─────────────────────────────────────────────────────────────────────┘
                        ▲
                        │ extends
        ┌───────────────┴───────────────┬────────────────────┐
        │                               │                    │
┌───────────────────┐           ┌───────────────────┐ ┌───────────────────┐
│ PerformanceMetrics│           │    SEOMetrics     │ │  SecurityMetrics  │
├───────────────────┤           ├───────────────────┤ ├───────────────────┤
│ +fcp: number      │           │ +title: string    │ │ +ssl: object      │
│ +lcp: number      │           │ +meta: object     │ │ +headers: object  │
│ +tti: number      │           │ +headings: object │ │ +vulnerabilities: │
│ +cls: number      │           │ +links: object    │ │ +cookiesSecurity: │
│ +totalSize: number│           │ +images: object   │ │ +csp: object      │
├───────────────────┤           ├───────────────────┤ ├───────────────────┤
│ +getTiming(): obj │           │ +getSEOIssues():  │ │ +getVulnCount():  │
└───────────────────┘           └───────────────────┘ └───────────────────┘

           ┌───────────────────┐           ┌───────────────────┐
           │ TechnicalMetrics  │           │   DomainInfo      │
           ├───────────────────┤           ├───────────────────┤
           │ +html: object     │           │ +registrar: string│
           │ +css: object      │           │ +creationDate: date│
           │ +javascript: obj  │           │ +expiryDate: date │
           │ +responsive: bool │           │ +nameservers: []  │
           │ +technologies: [] │           │ +ipAddress: string│
           ├───────────────────┤           ├───────────────────┤
           │ +getTechStack():  │           │ +getAge(): number │
           └───────────────────┘           └───────────────────┘
```

## 2. Struttura Dati - Formato JSON

### 2.1 Formato Completo

```json
{
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
      },
      "details": {
        "resources": {
          "js": 450000,
          "css": 120000,
          "images": 850000,
          "fonts": 80000
        },
        "resourceCount": 32,
        "renderBlocking": 3
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
      },
      "details": {
        "titleLength": 55,
        "metaDescription": 140,
        "hasCanonical": true,
        "imgAltTags": 85,
        "headingStructure": "Well structured",
        "linkQuality": "Good"
      }
    },
    "security": {
      "score": 92,
      "metrics": {
        "ssl": "A+",
        "headers": "Good",
        "vulnerabilities": 0,
        "cookies": "Secure",
        "csp": "Implemented"
      },
      "details": {
        "sslDetails": {
          "grade": "A+",
          "protocol": "TLS 1.3",
          "expiry": "2024-05-15"
        },
        "securityHeaders": {
          "strictTransportSecurity": true,
          "contentSecurityPolicy": true,
          "xFrameOptions": true,
          "xContentTypeOptions": true,
          "referrerPolicy": true
        },
        "cookiesDetails": {
          "secure": true,
          "httpOnly": true,
          "sameSite": "Strict"
        }
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
      },
      "details": {
        "htmlVersion": "HTML5",
        "cssVersion": "CSS3",
        "jsFeatures": ["ES6", "Modules"],
        "frameworks": ["jQuery 3.6.0"],
        "libraries": ["Animate.js", "Chart.js"],
        "serverInfo": {
          "server": "Nginx",
          "platform": "Linux"
        }
      }
    },
    "finalScore": 85.8
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
      },
      "details": {
        "resources": {
          "js": 750000,
          "css": 180000,
          "images": 1100000,
          "fonts": 170000
        },
        "resourceCount": 48,
        "renderBlocking": 6
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
      },
      "details": {
        "titleLength": 60,
        "metaDescription": 155,
        "hasCanonical": true,
        "imgAltTags": 95,
        "headingStructure": "Moderately structured",
        "linkQuality": "Average"
      }
    },
    "security": {
      "score": 78,
      "metrics": {
        "ssl": "A",
        "headers": "Average",
        "vulnerabilities": 1,
        "cookies": "Secure",
        "csp": "Partial"
      },
      "details": {
        "sslDetails": {
          "grade": "A",
          "protocol": "TLS 1.2",
          "expiry": "2023-11-20"
        },
        "securityHeaders": {
          "strictTransportSecurity": true,
          "contentSecurityPolicy": false,
          "xFrameOptions": true,
          "xContentTypeOptions": true,
          "referrerPolicy": false
        },
        "cookiesDetails": {
          "secure": true,
          "httpOnly": true,
          "sameSite": "Lax"
        }
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
      },
      "details": {
        "htmlVersion": "HTML5",
        "cssVersion": "CSS3",
        "jsFeatures": ["ES6"],
        "frameworks": ["Bootstrap 5.1"],
        "libraries": ["Particles.js"],
        "serverInfo": {
          "server": "Apache",
          "platform": "Linux"
        }
      }
    },
    "finalScore": 78.5
  },
  "winner": "site1",
  "comparison": {
    "performance": "site1",
    "seo": "site2",
    "security": "site1",
    "technical": "site1",
    "overallScore": "site1",
    "scoreGap": 7.3
  },
  "timestamp": 1633276850000
}
```

### 2.2 Formato Semplificato per UI

```json
{
  "site1": {
    "url": "https://example1.com",
    "scores": {
      "performance": 85,
      "seo": 78,
      "security": 92,
      "technical": 88,
      "final": 85.8
    },
    "highlights": {
      "strengths": ["Fast loading time", "Excellent security", "Modern technologies"],
      "weaknesses": ["Meta descriptions", "Image optimization"]
    }
  },
  "site2": {
    "url": "https://example2.com",
    "scores": {
      "performance": 75,
      "seo": 82,
      "security": 78,
      "technical": 79,
      "final": 78.5
    },
    "highlights": {
      "strengths": ["Strong SEO setup", "Excellent image tagging", "Mobile responsive"],
      "weaknesses": ["Slow loading time", "Missing security headers"]
    }
  },
  "winner": {
    "site": "site1",
    "name": "example1.com",
    "margin": "Clear win (7.3 points)"
  },
  "categoryWinners": {
    "performance": "site1",
    "seo": "site2",
    "security": "site1",
    "technical": "site1"
  }
}
```

## 3. Diagramma delle Interazioni con i Dati

```
┌───────────────────────────────────────────────────────────────────┐
│                 Data Interaction Flow                             │
│                                                                    │
│  ┌────────────┐          ┌────────────┐         ┌────────────┐    │
│  │            │ produces │            │ produces│            │    │
│  │ Analyzers  ├─────────>│ Raw Data   ├────────>│ Normalized │    │
│  │            │          │            │         │ Data       │    │
│  └────────────┘          └────────────┘         └─────┬──────┘    │
│                                                       │            │
│                                                       │            │
│                                                       ▼            │
│                                                 ┌────────────┐    │
│                                                 │            │    │
│                                                 │ Category   │    │
│                                                 │ Scores     │    │
│                                                 │            │    │
│                                                 └─────┬──────┘    │
│                                                       │            │
│                                                       │            │
│  ┌────────────┐          ┌────────────┐              │            │
│  │            │          │            │   uses       │            │
│  │ UI Display │<─────────┤ Results    │<─────────────┘            │
│  │            │ renders  │ Processor  │                          │
│  └────────────┘          └────────────┘                          │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

## 4. Diagramma ER per Risultati dell'Analisi

```
┌───────────────────┐       ┌───────────────────┐
│                   │       │                   │
│  AnalysisResult   │       │   SiteAnalysis    │
│                   │       │                   │
└─────────┬─────────┘       └─────────┬─────────┘
          │ 1                         │ 2
          │                           │
          │ has                       │ contains
          │                           │
          ▼ 1                         ▼ 1
┌───────────────────┐       ┌───────────────────┐
│                   │       │                   │
│  ComparisonResult │       │  DomainInfo       │
│                   │       │                   │
└───────────────────┘       └───────────────────┘
                                    │ 1
                                    │
                                    │ has
                                    │
                                    ▼ 4
┌───────────────────┐       ┌───────────────────┐
│                   │       │                   │
│  CategoryScore    │◄──────┤  CategoryMetrics  │
│                   │scores │                   │
└───────────────────┘       └─────────┬─────────┘
                                      │
                                      │ specializes to
                                      │
        ┌─────────────┬───────────────┼────────────┬──────────────┐
        │             │               │            │              │
        ▼ 1           ▼ 1             ▼ 1          ▼ 1            ▼ 1
┌───────────────┐ ┌───────────┐ ┌──────────┐ ┌────────────┐ ┌──────────────┐
│               │ │           │ │          │ │            │ │              │
│ Performance   │ │ SEO       │ │ Security │ │ Technical  │ │ Accessibility│
│ Metrics       │ │ Metrics   │ │ Metrics  │ │ Metrics    │ │ Metrics      │
│               │ │           │ │          │ │            │ │              │
└───────────────┘ └───────────┘ └──────────┘ └────────────┘ └──────────────┘
```

## 5. Diagramma di Aggregazione dei Dati

```
┌───────────────────────────────────────────────────────────────────┐
│                       Data Aggregation                             │
│                                                                    │
│  ┌────────────┐                                                   │
│  │            │                                                   │
│  │ Raw Metrics│                                                   │
│  │            │                                                   │
│  └─────┬──────┘                                                   │
│        │                                                          │
│        │ aggregated into                                          │
│        │                                                          │
│  ┌─────▼──────┐                                                   │
│  │            │                                                   │
│  │ Category   │                                                   │
│  │ Metrics    │                                                   │
│  │            │                                                   │
│  └─────┬──────┘                                                   │
│        │                                                          │
│        │ aggregated into                                          │
│        │                                                          │
│  ┌─────▼──────┐                                                   │
│  │            │                                                   │
│  │ Site       │                                                   │
│  │ Analysis   │                                                   │
│  │            │                                                   │
│  └─────┬──────┘                                                   │
│        │                                                          │
│        │ compared to create                                       │
│        │                                                          │
│  ┌─────▼──────┐                                                   │
│  │            │                                                   │
│  │ Analysis   │                                                   │
│  │ Result     │                                                   │
│  │            │                                                   │
│  └────────────┘                                                   │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

## 6. Dettaglio delle Entità di Dati Principali

### 6.1 AnalysisResult

**Responsabilità**: Rappresenta i risultati completi dell'analisi e del confronto tra due siti web.

**Attributi**:
- `site1`: Risultati completi dell'analisi del primo sito
- `site2`: Risultati completi dell'analisi del secondo sito
- `winner`: Indicatore del sito vincitore ("site1", "site2" o "tie")
- `comparison`: Risultati dettagliati del confronto
- `timestamp`: Data e ora dell'analisi

**Metodi**:
- `determineWinner()`: Determina il vincitore in base ai punteggi finali
- `getWinningCategories(site)`: Ottiene le categorie in cui un sito ha ottenuto i punteggi più alti
- `toJSON()`: Serializza i risultati in formato JSON

### 6.2 SiteAnalysis

**Responsabilità**: Contiene i dati di analisi completi per un singolo sito web.

**Attributi**:
- `url`: URL del sito analizzato
- `performance`: Metriche di performance
- `seo`: Metriche SEO
- `security`: Metriche di sicurezza
- `technical`: Metriche tecniche
- `finalScore`: Punteggio finale ponderato

**Metodi**:
- `calculateFinalScore()`: Calcola il punteggio finale ponderato
- `getHighestScoringCategory()`: Restituisce la categoria con il punteggio più alto
- `getLowestScoringCategory()`: Restituisce la categoria con il punteggio più basso

### 6.3 PerformanceMetrics

**Responsabilità**: Contiene le metriche di performance di un sito web.

**Attributi**:
- `score`: Punteggio complessivo di performance (0-100)
- `metrics`: Valori specifici delle metriche
  - `fcp`: First Contentful Paint (ms)
  - `lcp`: Largest Contentful Paint (ms)
  - `tti`: Time to Interactive (ms)
  - `cls`: Cumulative Layout Shift
  - `totalSize`: Dimensione totale della pagina (bytes)
- `details`: Dettagli aggiuntivi sulla performance

**Metodi**:
- `getScore()`: Restituisce il punteggio complessivo
- `getMetrics()`: Restituisce le metriche specifiche
- `getTiming()`: Restituisce i tempi di caricamento aggregati

### 6.4 SEOMetrics

**Responsabilità**: Contiene le metriche SEO di un sito web.

**Attributi**:
- `score`: Punteggio complessivo SEO (0-100)
- `metrics`: Valori specifici delle metriche
  - `title`: Valutazione del titolo
  - `meta`: Valutazione dei meta tag
  - `headings`: Valutazione della struttura dei titoli
  - `images`: Valutazione delle immagini
  - `links`: Valutazione dei link
- `details`: Dettagli aggiuntivi SEO

**Metodi**:
- `getScore()`: Restituisce il punteggio complessivo
- `getMetrics()`: Restituisce le metriche specifiche
- `getSEOIssues()`: Restituisce i problemi SEO rilevati

### 6.5 SecurityMetrics

**Responsabilità**: Contiene le metriche di sicurezza di un sito web.

**Attributi**:
- `score`: Punteggio complessivo di sicurezza (0-100)
- `metrics`: Valori specifici delle metriche
  - `ssl`: Valutazione SSL/TLS
  - `headers`: Valutazione degli header di sicurezza
  - `vulnerabilities`: Numero di vulnerabilità rilevate
  - `cookies`: Valutazione della sicurezza dei cookies
  - `csp`: Valutazione della Content Security Policy
- `details`: Dettagli aggiuntivi sulla sicurezza

**Metodi**:
- `getScore()`: Restituisce il punteggio complessivo
- `getMetrics()`: Restituisce le metriche specifiche
- `getVulnCount()`: Restituisce il numero di vulnerabilità

### 6.6 TechnicalMetrics

**Responsabilità**: Contiene le metriche tecniche di un sito web.

**Attributi**:
- `score`: Punteggio complessivo tecnico (0-100)
- `metrics`: Valori specifici delle metriche
  - `html`: Valutazione HTML
  - `css`: Valutazione CSS
  - `javascript`: Valutazione JavaScript
  - `responsive`: Flag di responsività
  - `technologies`: Array di tecnologie rilevate
- `details`: Dettagli aggiuntivi tecnici

**Metodi**:
- `getScore()`: Restituisce il punteggio complessivo
- `getMetrics()`: Restituisce le metriche specifiche
- `getTechStack()`: Restituisce lo stack tecnologico completo

### 6.7 ComparisonResult

**Responsabilità**: Contiene i risultati del confronto tra i due siti web.

**Attributi**:
- `performance`: Vincitore per la categoria performance
- `seo`: Vincitore per la categoria SEO
- `security`: Vincitore per la categoria sicurezza
- `technical`: Vincitore per la categoria tecnica
- `overallScore`: Vincitore complessivo
- `scoreGap`: Differenza tra i punteggi finali

**Metodi**:
- `getWinningCategories(site)`: Restituisce le categorie vinte da un sito
- `isCloseDuel()`: Determina se il confronto è stato equilibrato

## 7. Diagramma di Ereditarietà - Metriche

```
┌───────────────────────────────────────────────────────────────────┐
│                     Metrics Inheritance                            │
│                                                                    │
│  ┌────────────┐                                                   │
│  │            │                                                   │
│  │ BaseMetrics│                                                   │
│  │            │                                                   │
│  └─────┬──────┘                                                   │
│        │                                                          │
│        │ extends                                                  │
│        │                                                          │
│ ┌──────┴───────┬────────────────┬────────────────┬──────────────┐│
│ │              │                │                │              ││
│ ▼              ▼                ▼                ▼              ▼│
│┌────────────┐ ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐
││            │ │            │  │            │  │            │  │            │
││Performance │ │SEO         │  │Security    │  │Technical   │  │Accessibility│
││Metrics     │ │Metrics     │  │Metrics     │  │Metrics     │  │Metrics     │
││            │ │            │  │            │  │            │  │            │
│└────────────┘ └────────────┘  └────────────┘  └────────────┘  └────────────┘
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

## 8. Diagramma dei Punteggi

```
┌───────────────────────────────────────────────────────────────────┐
│                      Score Calculation                             │
│                                                                    │
│  ┌────────────┐   30%    ┌────────────┐   25%   ┌────────────┐    │
│  │            │─────────>│            │────────>│            │    │
│  │Performance │          │SEO         │         │Security    │    │
│  │Score       │          │Score       │         │Score       │    │
│  └────────────┘          └────────────┘         └────────────┘    │
│        │                                               │          │
│        │                                               │          │
│        │                       ┌────────────┐          │          │
│        │                       │            │          │          │
│        └──────────────────────>│Final Score │<─────────┘          │
│                       20%      │            │                     │
│                       ┌───────>│            │<───────┐            │
│                       │        └────────────┘        │            │
│                       │                              │            │
│                 ┌────────────┐                ┌────────────┐     │
│                 │            │                │            │     │
│                 │Technical   │                │Additional  │     │
│                 │Score       │                │Metrics     │     │
│                 └────────────┘                └────────────┘     │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

## 9. Diagramma di Persistenza

```
┌───────────────────────────────────────────────────────────────────┐
│                     Data Persistence                               │
│                                                                    │
│  ┌────────────┐                                                   │
│  │            │                                                   │
│  │ Analysis   │                                                   │
│  │ Results    │                                                   │
│  └─────┬──────┘                                                   │
│        │                                                          │
│        │ stored as                                                │
│        │                                                          │
│  ┌─────▼──────┐    cached in     ┌────────────┐                  │
│  │            │◄───────────────►│            │                  │
│  │ JSON       │                  │ Memory     │                  │
│  │ Files      │                  │ Cache      │                  │
│  └─────┬──────┘                  └────────────┘                  │
│        │                                                          │
│        │ exported as                                              │
│        │                                                          │
│  ┌─────▼──────┐                  ┌────────────┐                  │
│  │            │                  │            │                  │
│  │ CSV        │                  │ PDF        │                  │
│  │ Reports    │                  │ Reports    │                  │
│  └────────────┘                  └────────────┘                  │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

## 10. Specifica delle Interfacce dei Dati

### 10.1 Interfaccia AnalysisResult

```typescript
interface AnalysisResult {
  site1: SiteAnalysis;
  site2: SiteAnalysis;
  winner: 'site1' | 'site2' | 'tie';
  comparison: ComparisonResult;
  timestamp: number;
}
```

### 10.2 Interfaccia SiteAnalysis

```typescript
interface SiteAnalysis {
  url: string;
  performance: PerformanceMetrics;
  seo: SEOMetrics;
  security: SecurityMetrics;
  technical: TechnicalMetrics;
  finalScore: number;
}
```

### 10.3 Interfaccia BaseMetrics

```typescript
interface BaseMetrics {
  score: number;
  metrics: Record<string, any>;
  details: Record<string, any>;
}
```

### 10.4 Interfaccia PerformanceMetrics

```typescript
interface PerformanceMetrics extends BaseMetrics {
  metrics: {
    fcp: number;
    lcp: number;
    tti: number;
    cls: number;
    totalSize: number;
  };
  details: {
    resources: {
      js: number;
      css: number;
      images: number;
      fonts: number;
    };
    resourceCount: number;
    renderBlocking: number;
  };
}
```

### 10.5 Interfaccia SEOMetrics

```typescript
interface SEOMetrics extends BaseMetrics {
  metrics: {
    title: string;
    meta: string;
    headings: string;
    images: string;
    links: string;
  };
  details: {
    titleLength: number;
    metaDescription: number;
    hasCanonical: boolean;
    imgAltTags: number;
    headingStructure: string;
    linkQuality: string;
  };
}
```

### 10.6 Interfaccia SecurityMetrics

```typescript
interface SecurityMetrics extends BaseMetrics {
  metrics: {
    ssl: string;
    headers: string;
    vulnerabilities: number;
    cookies: string;
    csp: string;
  };
  details: {
    sslDetails: {
      grade: string;
      protocol: string;
      expiry: string;
    };
    securityHeaders: {
      strictTransportSecurity: boolean;
      contentSecurityPolicy: boolean;
      xFrameOptions: boolean;
      xContentTypeOptions: boolean;
      referrerPolicy: boolean;
    };
    cookiesDetails: {
      secure: boolean;
      httpOnly: boolean;
      sameSite: string;
    };
  };
}
```

### 10.7 Interfaccia TechnicalMetrics

```typescript
interface TechnicalMetrics extends BaseMetrics {
  metrics: {
    html: string;
    css: string;
    javascript: string;
    responsive: boolean;
    technologies: string[];
  };
  details: {
    htmlVersion: string;
    cssVersion: string;
    jsFeatures: string[];
    frameworks: string[];
    libraries: string[];
    serverInfo: {
      server: string;
      platform: string;
    };
  };
}
```

### 10.8 Interfaccia ComparisonResult

```typescript
interface ComparisonResult {
  performance: 'site1' | 'site2' | 'tie';
  seo: 'site1' | 'site2' | 'tie';
  security: 'site1' | 'site2' | 'tie';
  technical: 'site1' | 'site2' | 'tie';
  overallScore: 'site1' | 'site2' | 'tie';
  scoreGap: number;
}
```

## 11. Normalizzazione dei Dati

### 11.1 Diagramma di Normalizzazione

```
┌───────────────────────────────────────────────────────────────────┐
│                     Data Normalization                             │
│                                                                    │
│  ┌────────────┐          ┌────────────┐         ┌────────────┐    │
│  │            │          │            │         │            │    │
│  │ Raw Data   │─────────>│ Validation │────────>│ Type       │    │
│  │            │          │            │         │ Conversion │    │
│  └────────────┘          └────────────┘         └─────┬──────┘    │
│                                                       │            │
│                                                       │            │
│                                                       ▼            │
│                                                 ┌────────────┐    │
│                                                 │            │    │
│                                                 │ Scale      │    │
│                                                 │ Adjustment │    │
│                                                 │            │    │
│                                                 └─────┬──────┘    │
│                                                       │            │
│                                                       │            │
│  ┌────────────┐          ┌────────────┐              │            │
│  │            │          │            │              │            │
│  │ Normalized │<─────────┤ Final      │<─────────────┘            │
│  │ Data       │          │ Processing │                          │
│  └────────────┘          └────────────┘                          │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

### 11.2 Regole di Normalizzazione per Metriche Chiave

| Metrica | Valore Grezzo | Normalizzazione | Punteggio |
|---------|--------------|------------------|-----------|
| FCP | < 1000ms | Eccellente | 90-100 |
| FCP | 1000-2500ms | Buono | 60-89 |
| FCP | 2500-4000ms | Migliorabile | 30-59 |
| FCP | > 4000ms | Scarso | 0-29 |
| LCP | < 2500ms | Eccellente | 90-100 |
| LCP | 2500-4000ms | Buono | 60-89 |
| LCP | 4000-6000ms | Migliorabile | 30-59 |
| LCP | > 6000ms | Scarso | 0-29 |
| CLS | < 0.1 | Eccellente | 90-100 |
| CLS | 0.1-0.25 | Buono | 60-89 |
| CLS | 0.25-0.4 | Migliorabile | 30-59 |
| CLS | > 0.4 | Scarso | 0-29 |
| SSL Grade | A+ | Eccellente | 90-100 |
| SSL Grade | A | Molto Buono | 80-89 |
| SSL Grade | B | Buono | 70-79 |
| SSL Grade | C | Migliorabile | 50-69 |
| SSL Grade | F | Scarso | 0-49 |

## 12. Diagramma di Relazioni tra Metriche

```
┌───────────────────────────────────────────────────────────────────┐
│                     Metrics Relationships                          │
│                                                                    │
│                     ┌────────────┐                                │
│                     │            │                                │
│                     │Performance │                                │
│                     │            │                                │
│                     └─────┬──────┘                                │
│                           │                                       │
│                           │ affects                               │
│                           │                                       │
│                     ┌─────▼──────┐                                │
│                     │            │                                │
│  ┌────────────┐     │User        │    ┌────────────┐             │
│  │            │     │Experience  │    │            │             │
│  │Technical   │────>│            │<───┤Accessibility│             │
│  │            │     │            │    │            │             │
│  └────────────┘     └─────┬──────┘    └────────────┘             │
│                           │                                       │
│                           │ contributes to                        │
│                           │                                       │
│                     ┌─────▼──────┐                                │
│  ┌────────────┐     │            │     ┌────────────┐            │
│  │            │────>│Overall     │<────┤            │            │
│  │Security    │     │Quality     │     │SEO         │            │
│  │            │     │            │     │            │            │
│  └────────────┘     └────────────┘     └────────────┘            │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

## 13. Diagramma della Rappresentazione Visiva dei Dati

```
┌───────────────────────────────────────────────────────────────────┐
│                     Data Visualization                             │
│                                                                    │
│  ┌────────────┐          ┌────────────┐         ┌────────────┐    │
│  │            │ format   │            │ render  │            │    │
│  │ Analysis   ├─────────>│ Chart      ├────────>│ Radar      │    │
│  │ Results    │ for      │ Data       │ as      │ Chart      │    │
│  └────────────┘ charts   └────────────┘         └────────────┘    │
│        │                                                          │
│        │ format                                                   │
│        │ for tables                                               │
│        ▼                       ┌────────────┐     ┌────────────┐  │
│  ┌────────────┐      render    │            │     │            │  │
│  │            │─────────────>│ Comparison │     │ Score      │  │
│  │ Table      │      as       │ Table      │     │ Gauges     │  │
│  │ Data       ├──────────────────────────┐ │     │            │  │
│  └────────────┘                          │ │     └────────────┘  │
│        │                                 ▼ ▼                     │
│        │ format                    ┌────────────┐                │
│        │ for details               │            │                │
│        ▼                           │ Detailed   │                │
│  ┌────────────┐                    │ Metrics    │                │
│  │            │                    │ View       │                │
│  │ Detailed   │                    │            │                │
│  │ Data       │                    └────────────┘                │
│  └────────────┘                                                  │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```