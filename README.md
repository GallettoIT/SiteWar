# Site War

Site War è un tool di web testing comparativo che presenta l'analisi come una "guerra" tra siti. Il sistema analizza due siti web simultaneamente, valutandoli su vari aspetti tecnici, e presenta i risultati come una battaglia con un vincitore finale.

## Caratteristiche Principali

- **Confronto Diretto**: analisi comparativa di due siti web qualsiasi
- **Analisi Completa**: valutazione di Performance (30%), SEO (25%), Sicurezza (25%), Aspetti tecnici (20%)
- **Motore di Analisi Avanzato**: include 6 tipi di analizzatori specializzati e integrazione con API esterne
- **Esecuzione Parallela**: analisi simultanea dei siti con gestione timeout e priorità
- **Visualizzazione Coinvolgente**: rappresentazione della "guerra" tra siti con animazioni interattive
- **Dashboard Interattiva**: grafici comparativi e tabelle dettagliate per ogni categoria
- **Proclamazione Vincitore**: determinazione oggettiva basata su punteggio ponderato con analisi punti di forza
- **Performance Elevata**: analisi completa in massimo 25 secondi
- **Esportazione Dati**: possibilità di esportare i risultati in CSV con supporto per stampa

## Architettura

Site War implementa un'architettura client-server con elaborazione distribuita:

- **Frontend (65%)**: HTML5, CSS3, JavaScript, con pattern di design modulari
- **Backend (25%)**: PHP puro (senza framework), con API RESTful e analizzatori specializzati
- **API Esterne (10%)**: Integrazione con servizi di analisi specializzati

## Requisiti Tecnici

- PHP 7.4 o superiore
- Server web (Apache/Nginx)
- Browser moderno con supporto JavaScript ES6+
- Connessione internet (per API esterne)
- Account e chiavi API per servizi esterni (opzionali ma consigliati)

## Installazione

1. Clonare il repository:
```bash
git clone https://github.com/yourusername/site-war.git
```

2. Configurare un server web con PHP (Apache/Nginx) puntando alla directory principale

3. Copiare e configurare il file delle chiavi API:
```bash
cp server/config/api_keys.example.php server/config/api_keys.php
# Modificare api_keys.php con le proprie chiavi
```

4. Assicurarsi che le directory cache siano scrivibili:
```bash
chmod -R 755 server/cache
```

## Struttura del Progetto

```
site-war/
│
├── assets/                   # Risorse frontend
│   ├── css/                  # Fogli di stile
│   │   ├── main.css          # Stili principali
│   │   ├── animations.css    # Animazioni battaglia
│   │   └── print.css         # Stili per stampa
│   ├── js/                   # JavaScript
│   │   └── main.js           # JavaScript modulare completo
│   │       ├── App           # Controller principale
│   │       ├── EventBus      # Sistema eventi
│   │       ├── APIConnector  # Client API backend
│   │       ├── FormUI        # Gestione form input
│   │       ├── BattleUI      # Animazioni battaglia
│   │       └── ResultsUI     # Dashboard risultati
│   └── images/               # Immagini e icone
│
├── server/                   # Backend PHP
│   ├── api/                  # API endpoints
│   │   ├── index.php         # Entry point API
│   │   └── controllers/      # Controller specifici
│   ├── config/               # Configurazioni
│   ├── core/                 # Framework di base
│   ├── services/             # Servizi e analizzatori
│   │   ├── analyzers/        # Analizzatori specifici
│   │   │   ├── BaseAnalyzer.php        # Classe base astratta
│   │   │   ├── SEOAnalyzer.php         # Analisi SEO
│   │   │   ├── SecurityAnalyzer.php    # Analisi sicurezza
│   │   │   ├── PerformanceAnalyzer.php # Analisi performance
│   │   │   ├── TechnologyAnalyzer.php  # Analisi tecnologie
│   │   │   └── DOMAnalyzer.php         # Analisi DOM
│   │   └── AnalysisManager.php         # Coordinamento analisi
│   └── utils/                # Utilities
│       └── cache/            # Storage cache
│
├── docs/                     # Documentazione tecnica
├── templates/                # Template PHP
│   ├── SiteWarApp.php        # Configurazione applicazione
│   ├── ErrorView.php         # Visualizzazione errori
│   └── ResultViewer.php      # Visualizzazione report
├── index.php                 # Entry point applicazione
├── README.md                 # Documentazione generale
├── DEVELOPMENT_GUIDE.md      # Guida allo sviluppo
└── NEXT_STEPS.md             # Prossimi sviluppi
```

## Pattern Implementati

Site War utilizza i seguenti pattern di design:

- **Module Pattern**: per l'organizzazione del codice JavaScript in moduli autonomi
- **Factory Method**: per la creazione di analizzatori e servizi tramite ServiceFactory
- **Observer Pattern**: per la comunicazione tra moduli tramite EventBus
- **Strategy Pattern**: per implementare diverse strategie di analisi nei vari analizzatori
- **Template Method**: nei BaseAnalyzer per definire il flusso comune dell'analisi
- **Adapter Pattern**: per interfacciarsi con diverse API esterne in modo uniforme
- **Proxy Pattern**: per proteggere le chiavi API e implementare caching multi-livello
- **Facade Pattern**: in AnalysisManager per semplificare l'interazione con il sistema complesso
- **Composite Pattern**: nel TechnologyAnalyzer per la struttura dei pattern di rilevamento
- **Command Pattern**: nei controller per l'elaborazione delle richieste
- **MVC Pattern (adattato)**: per separare dati, logica e presentazione in tutto il sistema

## Utilizzo

1. Accedere all'applicazione tramite browser
2. Inserire gli URL dei due siti da confrontare
3. Attendere il completamento dell'analisi (max 25 secondi)
4. Osservare le animazioni della "guerra" tra i siti
5. Esplorare i risultati dettagliati nella dashboard comparativa
6. Esportare i risultati se necessario (CSV/HTML)

## API Implementate

Site War espone le seguenti API:

- **`/api/validate`**: Validazione degli URL e pertinenza del confronto (con integrazione OpenAI)
- **`/api/analyze`**: Avvio dell'analisi comparativa con esecuzione parallela
- **`/api/progress`**: Monitoraggio dell'avanzamento dell'analisi in tempo reale
- **`/api/report`**: Generazione report in diversi formati con supporto per CSV e HTML

## Analizzatori Implementati

Il sistema include i seguenti analizzatori specializzati:

- **SEOAnalyzer**: analisi di meta tag, struttura heading, link, immagini e altro
- **SecurityAnalyzer**: valutazione header di sicurezza, SSL/TLS, vulnerabilità e protezioni
- **PerformanceAnalyzer**: integrazione con Google PageSpeed API, analisi tempi di caricamento
- **TechnologyAnalyzer**: rilevamento CMS, framework, librerie e stack tecnologico
- **DOMAnalyzer**: analisi semantica, accessibilità, struttura DOM e responsive design

## API Esterne Integrate

Il sistema può integrarsi con:

- **Google PageSpeed Insights**: analisi performance e core web vitals
- **Moz API**: metriche SEO avanzate
- **Security Headers**: valutazione sicurezza HTTP
- **WHOIS API**: informazioni dominio
- **W3C Validator**: validazione HTML/CSS
- **OpenAI API**: validazione pertinenza del confronto

## Documentazione

La documentazione completa è disponibile nella directory `docs/` e include:
- Architettura dettagliata
- Componenti e loro interazioni
- Diagrammi UML
- Casi d'uso
- Documentazione API

## Sviluppo

Per contribuire al progetto, consultare `DEVELOPMENT_GUIDE.md` per linee guida dettagliate. I prossimi passi di sviluppo sono elencati in `NEXT_STEPS.md`.

## Licenza

Questo progetto è rilasciato sotto licenza MIT.

---

Sviluppato con ❤️ per rendere il web testing tecnico più coinvolgente e interattivo.