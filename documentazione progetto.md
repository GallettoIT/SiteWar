**DATI ESSENZIALI PER IL PROMPT**

**Titolo del progetto:** Site War

**Descrizione:** Site War rappresenta uno strumento di web testing che analizza in modo comparativo due siti web, presentando la valutazione tecnica come una “guerra” con la proclamazione finale di un vincitore. Questo approccio creativo mira a rendere il web testing tecnico più coinvolgente e accessibile.

1. **Overview e obiettivi**

* **Descrizione:** l’applicazione web in questione è un tool di web testing che analizza in modo avanzato due siti web passati dall’utente tramite URL. L’obiettivo principale è ottenere un confronto e un’analisi accurata dei due siti web sfidanti come se fossero in guerra tecnica tra loro. Al termine, oltre ad ottenere i risultati dettagliati, verrà proclamato il vincitore tra i due siti.

  Il tester deve preferibilmente validare tutte le componenti dei due siti, quindi deve poter avere una visione completa di quali siano le componenti dei siti costruendo eventuali strutture/grafi di supporto.

  L’applicazione è pensata per rivolgersi ai seguenti target di utenza:

* creatori e/o sviluppatori web che vogliono confrontare il proprio sito con siti concorrenti

  utenti curiosi che desiderano avere maggiori informazioni tecniche riguardo due siti per valutare l’efficienza, l’efficacia e la sicurezza per decretarne il migliore da utilizzare dal 	punto di vista tecnico

* penetration tester in cerca di vulnerabilità e differenze tecniche o similitudini tra siti

* **Obiettivi di business**:

  * \- rendere più coinvolgente, giocoso e leggero un processo estremamente tecnico e avanzato quale il web testing tramite il concetto della “guerra tra siti”

    * rendere il progetto redditizio tramite pubblicità poco invasive

2. **Requisiti funzionali e non funzionali**

* **Requisiti funzionali**:

  * Inserimento degli URL dei due siti

  * Utilizzo dell’AI per valutare se i due URL appartengono a siti dei quali ha senso eseguire il confronto

  * Analisi generica (linguaggi frontend, framework, CMS, versioni, IP, ecc… )

  * Analisi dei SEO

  * Analisi delle vulnerabilità

  * Analisi delle prestazioni

  * Confronto basato sulle analisi per decretare il vincitore della “guerra tra siti”

* **Requisiti non funzionali**:

  * Performance & UI: durante il tempo di risposta per l’esecuzione di ogni test bisogna intrattenere l’utente tramite animazioni che illustrano in modo creativo la “guerra tra i siti” basata sui risultati real-time delle analisi e dei relativi confronti. Ogni test completo non dovrebbe superare i 25 secondi di tempo di risposta

  * Scalabilità: la maggior parte delle analisi e dei confronti avviene tramite scraping e algoritmi lato client

  * Sicurezza: tutte le comunicazioni devono avvenire tramite protocolli sicuri come HTTPS, gli algoritmi lato client devono rimanere il più possibile segreti all’utente e non iniettabili, le apikey devono rimanere segrete

  * Usabilità: l’interfaccia utente deve essere intuitiva e accessibile, con layout responsive che garantiscano una buona esperienza sia su desktop che su dispositivi mobile. La UI deve essere pensata per rendere il processo divertente seppur mostrando tutti i dettagli tecnici delle analisi e confronto

  * Manutenibilità: il codice deve essere strutturato in maniera modulare seguendo le linee guida date dall’ingegneria del software implementando i relativi design pattern e ben documentato

  * Compatibilità: l’applicativo deve funzionare correttamente sui principali browser (Chrome, Firefox, Safari, Edge) e su diverse piattaforme/ambienti operativi  
      
      
3. **Architettura del sistema**

* **Overview**: L’architettura di Site War si basa su un modello client-server con elaborazione distribuita. La maggior parte delle analisi viene eseguita lato client per ottimizzare le prestazioni e ridurre il carico sul server, rispettando il vincolo di completare le analisi entro 25 secondi.

* **Componenti chiave:**

  * **Interfaccia Utente (Frontend)**  
    * Modulo di inserimento URL  
      * Sistema di validazione preliminare  
        * Motore di visualizzazione delle animazioni “guerra”  
          * Dashboard di risultati comparativi  
          * Pannello di dettaglio per ogni categoria di analisi  
  * **Motore di Analisi (Client)**  
    * Analizzatore DOM e struttura pagina  
      * Modulo di performance testing  
        * Rilevatore tecnologie frontend  
          * Scraper SEO di base  
  * **Servizi Backend (Server)**  
    * API controller per orchestrare le richieste  
      * Servizio di validazione AI per la pertinenza del confronto  
        * Proxy sicuro per le chiamate API esterne  
          * Analizzatore avanzato (per operazioni non eseguibili lato client)  
          * Generatore rapporto finale e proclamazione vincitore

* **Interazioni**:

  1. L’utente inserisce gli URL dei due siti da confrontare  
     2. Il frontend esegue una validazione preliminare (formato URL valido)  
        3. Il backend verifica tramite AI la pertinenza del confronto  
           4. Il client avvia le analisi eseguibili lato browser, visualizzando animazioni durante l’elaborazione  
           5. Il server esegue parallelamente le analisi più complesse e quelle che richiedono API esterne  
           6. I risultati vengono progressivamente visualizzati nella UI come “battaglie” tra i siti  
           7. Al termine, il sistema calcola il punteggio complessivo e proclama il vincitore  
4. **Tecnologie e stack**

* **Vincoli progettuali su linguaggi/framework**  
  * Lato server: PHP

  * Lato client: HTML5, CSS3, JAVASCRIPT

  * Non è consentito l’uso di framework (sia lato server che client) di tipo architetturale (es. typescript, angular, react, node.js, lavarel) ad eccezione di jquery

  * E’ consentito l’uso di framework con licenza gratuita per scopi non commerciali per l’implementazione di particolari componenti grafiche, ad esempio Bootstrap.

* **Framework e librerie scelti:**  
  * **jQuery**: Per manipolazione DOM e AJAX  
  * **Bootstrap 5**: Per componenti UI responsive  
  * **Chart.js**: Visualizzazione dei dati comparativi  
  * **Anime.js**: Animazioni fluide per la “guerra” tra siti  
  * **Particles.js**: Effetti visivi durante le analisi  
  * **Papa Parse**: Parsing CSV per l’esportazione dei risultati  
* **Database e storage:** attualmente abbiamo pensato ad una soluzione che non prevede registrazione dell’utente e storage dei dati. Preferiamo concentrarci sulle funzionalità core.

* **Strumenti di DevOps:**  
  * **Git**: Controllo versione del codice  
  * **Docker**: Containerizzazione per lo sviluppo e il deployment  
  * **Jenkins**: CI/CD per automazione build e test  
  * **Lighthouse CLI**: Integrazione test di performance  
  * **OWASP ZAP**: Test di sicurezza automatizzati


5. **Design Patterns e best practices**

* **Pattern applicabili:**  
  1. **Module Pattern**: Organizzazione del codice JavaScript in moduli autonomi con responsabilità ben definite, migliorando la manutenibilità e prevenendo conflitti di namespace.  
     2. **Factory Method**: Creazione di analizzatori specifici per ciascun tipo di test senza accoppiare il codice a classi concrete.  
        3. **Observer Pattern**: Notifica dei risultati di analisi completati ai vari componenti dell’UI, permettendo aggiornamenti in tempo reale.  
        4. **Strategy Pattern**: Implementazione di diverse strategie di analisi e confronto, permettendo di cambiarli dinamicamente in base alle necessità.  
        5. **Adapter Pattern**: Integrazione uniforme con diverse API esterne attraverso un’interfaccia standardizzata.  
        6. **Facade Pattern**: Interfaccia semplificata per il complesso sistema di analisi sottostante.  
        7. **MVC Pattern Adattato**: Separazione tra dati (Model), logica (Controller) e presentazione (View) anche senza framework MVC.  
* **Linee guida:**  
  1. **Codice Modulare**  
     * Separazione netta tra logica di business e presentazione  
     * File JavaScript organizzati per funzionalità  
     * Utilizzo di namespace per evitare conflitti  
  2. **Performance**  
     * Minimizzazione e compressione degli asset  
     * Caricamento asincrono dei moduli non essenziali  
     * Implementazione di tecniche di lazy loading  
     * Ottimizzazione delle query DOM  
  3. **Sicurezza**  
     * Sanitizzazione degli input utente  
     * Implementazione di CSP (Content Security Policy)  
     * Protezione contro attacchi XSS e CSRF  
     * Obfuscation del codice JavaScript critico  
  4. **Accessibilità**  
     * Conformità WCAG 2.1 AA  
     * Test con screen reader  
     * Supporto navigazione da tastiera  
     * Contrasto colori adeguato

* **Struttura dei file e del progetto:**

  site-war/

  │

  ├── assets/                   \# Risorse statiche

  │   ├── css/                  \# Fogli di stile

  │   │   ├── main.css          \# Stile principale

  │   │   ├── animations.css    \# Animazioni della "guerra"

  │   │   ├── components/       \# Stili per componenti specifici

  │   │   └── vendors/          \# CSS di terze parti (Bootstrap)

  │   │

  │   ├── js/                   \# JavaScript

  │   │   ├── main.js           \# Entry point

  │   │   ├── modules/          \# Moduli funzionali

  │   │   │   ├── analyzers/    \# Moduli di analisi

  │   │   │   ├── ui/           \# Componenti UI

  │   │   │   ├── core/         \# Funzionalità core

  │   │   │   └── comparison/   \# Logica di confronto

  │   │   │

  │   │   └── vendors/          \# Librerie di terze parti

  │   │

  │   └── images/               \# Immagini e icone

  │

  ├── server/                   \# Backend PHP

  │   ├── api/                  \# Endpoint API

  │   ├── config/               \# Configurazioni

  │   ├── core/                 \# Funzionalità core

  │   ├── services/             \# Servizi di business logic

  │   └── utils/                \# Utility functions

  │

  ├── templates/                \# Template HTML modulari

  │   ├── components/           \# Componenti riutilizzabili

  │   └── pages/                \# Template pagine

  │

  ├── tests/                    \# Test unitari e funzionali

  │

  ├── index.php                 \# Entry point applicazione

  ├── .htaccess                 \# Configurazione Apache

  └── README.md                 \# Documentazione

6. **Integrazioni e dipendenze esterne**

* **API e servizi terzi:**  
  1. **Google PageSpeed Insights**: Analisi performance  
     2. **Moz API**: Metriche SEO avanzate  
        3. **Security Headers**: Analisi sicurezza header HTTP  
           4. **WHOIS API**: Informazioni registrazione domini  
           5. **HTML Validator W3C**: Validazione standard HTML  
           6. **CSS Validator W3C**: Validazione CSS  
           7. **OpenAI API**: Validazione pertinenza confronto  
           8. **Cloudflare Workers**: Proxy per alcune richieste API  
* **Interfacce e contratti:**  
  * Implementazione di wrapper standardizzati per tutte le API esterne  
  * Cache dei risultati API per ridurre chiamate ripetute  
  * Gestione centralizzata degli errori API  
  * Implementazione di fallback per servizi non disponibili  
  * Sistema di rate limiting per prevenire sovraccarichi  
  * Limiti giornalieri di utilizzo per prevenire costi eccessivi