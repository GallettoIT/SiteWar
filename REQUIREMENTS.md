# Site War - Requisiti del Progetto

## Overview del Progetto
Site War è un tool di web testing comparativo che presenta l'analisi come una "guerra" tra siti. Il sistema analizza due siti web contemporaneamente, valutandoli su vari aspetti tecnici, e presenta i risultati come una battaglia con un vincitore finale.

## Obiettivi Principali
- Rendere coinvolgente e interattivo il web testing tecnico
- Fornire analisi comparative dettagliate di due siti web
- Completare tutte le analisi entro un limite di 25 secondi
- Creare un'esperienza visivamente accattivante con animazioni a tema "guerra"

## Requisiti Funzionali

### Inserimento e Validazione URL
- Input di due URL di siti web da confrontare
- Validazione del formato e della disponibilità degli URL
- Utilizzo di AI per valutare la pertinenza del confronto tra i siti

### Analisi dei Siti Web
- **Analisi DOM e Struttura**: Valutazione della struttura HTML, heading, elementi semantici
- **Analisi SEO**: Meta tag, struttura URL, alt text per immagini, schema markup
- **Analisi Performance**: FCP, LCP, TTI, CLS, dimensione pagina
- **Analisi Sicurezza**: Header HTTP, SSL/TLS, vulnerabilità potenziali
- **Analisi Tecnologia**: Identificazione linguaggi, framework, CMS utilizzati

### Visualizzazione e Confronto
- Animazione della "guerra" tra siti durante l'analisi
- Dashboard comparativa con grafici e metriche side-by-side
- Proclamazione di un vincitore basato su un sistema di punteggio ponderato
- Visualizzazione dettagliata dei singoli aspetti analizzati

### Esportazione Risultati
- Possibilità di esportare i risultati in formato CSV

## Requisiti Non Funzionali

### Performance
- Tempo massimo per completare l'analisi: 25 secondi
- Distribuzione del carico: Client (65%), Server (25%), API esterne (10%)
- Ottimizzazione delle chiamate API tramite caching

### UI/UX
- Interfaccia responsiva (desktop e mobile)
- Animazioni fluide che rappresentano la "guerra"
- Visualizzazioni accattivanti dei risultati comparativi
- Conformità WCAG 2.1 AA per accessibilità

### Sicurezza
- Sanitizzazione degli input utente
- Protezione delle chiavi API
- Implementazione di CSP (Content Security Policy)
- Utilizzo di HTTPS per tutte le comunicazioni

### Tecnologie

#### Vincoli Tecnologici
- **Backend**: PHP puro (senza framework)
- **Frontend**: HTML5, CSS3, JavaScript
- **Librerie consentite**: jQuery, Bootstrap 5, Chart.js, Anime.js, Particles.js, Papa Parse

#### API Esterne
- Google PageSpeed Insights (performance)
- Moz API (SEO)
- Security Headers (sicurezza)
- WHOIS API (informazioni dominio)
- HTML/CSS Validator W3C (validazione standard)
- OpenAI API (validazione pertinenza)

## Sistema di Punteggio
- Performance: 30%
- SEO: 25%
- Sicurezza: 25%
- Aspetti tecnici: 20%

## Design Patterns da Implementare
- Module Pattern
- Factory Method
- Observer Pattern
- Strategy Pattern
- Adapter Pattern
- Facade Pattern
- MVC Pattern (adattato)
- Proxy Pattern