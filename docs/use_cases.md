# Casi d'Uso - Site War

## 1. Panoramica

Questo documento descrive i casi d'uso principali del sistema Site War, dettagliando le interazioni tra gli utenti e il sistema per le varie funzionalità offerte dall'applicazione.

## 2. Attori

- **Utente Generico**: Qualsiasi visitatore del sito Web War
- **Sviluppatore Web**: Utente che utilizza il sistema per confrontare il proprio sito con siti concorrenti
- **Analista SEO/Performance**: Utente che utilizza il sistema per ottenere dati tecnici comparativi
- **Penetration Tester**: Utente interessato alle vulnerabilità e agli aspetti di sicurezza

## 3. Diagramma dei Casi d'Uso

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                     Site War                                             │
│                                                                                         │
│  ┌───────────────┐         ┌───────────────┐        ┌───────────────┐                   │
│  │               │         │               │        │               │                   │
│  │ UC1: Inserire │         │ UC2: Validare │        │ UC3: Avviare  │                   │
│  │    URL        │────────>│    URL        │───────>│   Analisi     │                   │
│  │               │         │               │        │               │                   │
│  └───────────────┘         └───────────────┘        └───────┬───────┘                   │
│          │                                                  │                           │
│          │                                                  │                           │
│          │                                                  ▼                           │
│  ┌───────────────┐         ┌───────────────┐        ┌───────────────┐                   │
│  │               │         │               │        │               │                   │
│  │ UC7: Esportare│<────────│ UC6: Esplorare│<───────│ UC4: Visualiz-│                   │
│  │   Risultati   │         │   Dettagli    │        │zare Animazioni│                   │
│  │               │         │               │        │               │                   │
│  └───────────────┘         └───────────────┘        └───────┬───────┘                   │
│                                   ▲                         │                           │
│                                   │                         │                           │
│                                   │                         ▼                           │
│                           ┌───────────────┐         ┌───────────────┐                   │
│                           │               │         │               │                   │
│                           │ UC8: Analizzare│<────────│ UC5: Visualiz-│                   │
│                           │   Vincitore   │         │zare Risultati │                   │
│                           │               │         │               │                   │
│                           └───────────────┘         └───────────────┘                   │
│                                                                                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

## 4. Descrizioni Dettagliate dei Casi d'Uso

### UC1: Inserire URL

**Attori Principali**: Tutti gli utenti  
**Pre-condizioni**: L'utente ha aperto il sito Site War  
**Trigger**: L'utente desidera confrontare due siti web  

**Scenario Principale di Successo**:
1. L'utente accede alla homepage di Site War
2. Il sistema presenta un form con due campi per l'inserimento degli URL
3. L'utente inserisce l'URL del primo sito nel campo "Sito 1"
4. L'utente inserisce l'URL del secondo sito nel campo "Sito 2"
5. L'utente fa clic sul pulsante "Inizia la battaglia!"
6. Il sistema procede con la validazione degli URL (UC2)

**Estensioni**:
- 3-4a. L'utente inserisce un URL in formato non valido
  1. Il sistema evidenzia il campo con errore
  2. Il sistema mostra un messaggio di errore specifico
  3. L'utente corregge l'input e riprova
- 5a. L'utente decide di cambiare uno degli URL inseriti
  1. L'utente modifica uno o entrambi i campi URL
  2. L'utente fa clic sul pulsante "Inizia la battaglia!"

**Requisiti Speciali**:
- Tempo di risposta: la validazione del formato URL deve essere immediata (client-side)
- I campi del form devono essere accessibili da tastiera
- Il form deve essere responsive per diversi dispositivi

**Frequenza**: Molto frequente (ogni analisi inizia da qui)

### UC2: Validare URL

**Attori Principali**: Sistema  
**Pre-condizioni**: L'utente ha inserito due URL e inviato il form  
**Trigger**: Invio del form di input URL  

**Scenario Principale di Successo**:
1. Il sistema verifica che entrambi gli URL siano in formato valido
2. Il sistema verifica che entrambi i siti siano accessibili
3. Il sistema utilizza l'AI per valutare la pertinenza del confronto tra i due siti
4. L'AI conferma che i siti sono confrontabili
5. Il sistema procede con l'avvio dell'analisi (UC3)

**Estensioni**:
- 2a. Uno o entrambi i siti non sono accessibili
  1. Il sistema mostra un messaggio di errore specifico
  2. L'utente viene invitato a verificare gli URL e riprovare
- 4a. L'AI determina che i siti non sono confrontabili
  1. Il sistema mostra un messaggio che spiega perché i siti non sono confrontabili
  2. L'utente può scegliere di procedere comunque con l'analisi o modificare gli URL
- 4b. Il servizio AI non è disponibile
  1. Il sistema procede con l'analisi senza la validazione di pertinenza
  2. Il sistema mostra un avviso che la validazione di pertinenza non è stata eseguita

**Requisiti Speciali**:
- Timeout: la validazione completa non deve superare i 5 secondi
- In caso di problemi con l'API AI, il sistema deve degradare in modo elegante

**Frequenza**: Molto frequente (ogni analisi richiede validazione)

### UC3: Avviare Analisi

**Attori Principali**: Sistema  
**Pre-condizioni**: Gli URL sono stati validati e sono confrontabili  
**Trigger**: Completamento della validazione URL  

**Scenario Principale di Successo**:
1. Il sistema mostra l'interfaccia di "battaglia" con i due siti
2. Il sistema avvia le analisi lato client (DOM, Performance base, SEO base)
3. Il sistema invia richieste al backend per le analisi più avanzate
4. Il sistema aggiorna la visualizzazione dell'avanzamento dell'analisi
5. Il backend elabora le richieste e comunica con API esterne quando necessario
6. Il sistema riceve progressivamente i risultati e aggiorna l'interfaccia
7. Al completamento di tutte le analisi, il sistema procede alla visualizzazione dei risultati (UC5)

**Estensioni**:
- 3a. Errore di comunicazione con il backend
  1. Il sistema mostra un messaggio di errore
  2. L'utente può riprovare l'analisi
- 5a. Errore di comunicazione con API esterne
  1. Il sistema utilizza dati parziali o strategie di fallback
  2. Il sistema continua con analisi alternative disponibili
  3. I risultati saranno marcati come parziali

**Requisiti Speciali**:
- Performance: l'analisi completa deve essere completata entro 25 secondi
- Il sistema deve mostrare un indicatore di avanzamento accurato
- Le analisi devono avvenire in parallelo quando possibile

**Frequenza**: Molto frequente (ogni analisi passa per questa fase)

### UC4: Visualizzare Animazioni

**Attori Principali**: Utente  
**Pre-condizioni**: L'analisi è stata avviata  
**Trigger**: Avanzamento dell'analisi  

**Scenario Principale di Successo**:
1. Il sistema visualizza un'animazione iniziale che rappresenta i due siti come "guerrieri"
2. Man mano che l'analisi procede, il sistema aggiorna l'animazione con effetti visivi
3. Le prestazioni relative dei siti influenzano l'animazione (es. il sito più veloce sembra "attaccare" l'altro)
4. Al raggiungimento di soglie di progresso (25%, 50%, 75%), l'animazione cambia fase
5. Quando l'analisi è completa, l'animazione mostra l'effetto finale di "vittoria"
6. Il sistema passa alla visualizzazione dei risultati (UC5)

**Estensioni**:
- 2a. L'utente usa un dispositivo a basse prestazioni
  1. Il sistema rileva le capacità del dispositivo
  2. Il sistema mostra animazioni semplificate per garantire performance adeguate

**Requisiti Speciali**:
- Le animazioni devono essere fluide (60 fps)
- Il sistema deve adattare le animazioni in base alle capacità del dispositivo
- L'accessibilità deve essere garantita anche con animazioni attive

**Frequenza**: Molto frequente (ogni analisi include animazioni)

### UC5: Visualizzare Risultati

**Attori Principali**: Utente  
**Pre-condizioni**: L'analisi è stata completata  
**Trigger**: Completamento dell'analisi  

**Scenario Principale di Successo**:
1. Il sistema mostra una dashboard con la proclamazione del vincitore
2. Il sistema visualizza i punteggi complessivi per entrambi i siti
3. Il sistema mostra un confronto visivo delle principali categorie di analisi
4. L'utente può visualizzare i dettagli di ogni categoria tramite tab
5. L'utente può decidere di esplorare i dettagli specifici (UC6)
6. L'utente può esportare i risultati (UC7)

**Estensioni**:
- 1a. L'analisi ha prodotto risultati parziali
  1. Il sistema mostra un avviso che alcuni dati potrebbero essere incompleti
  2. Il sistema indica quali analisi sono state completate con successo
- 4a. L'utente desidera confrontare una metrica specifica
  1. L'utente seleziona la categoria desiderata
  2. Il sistema mostra una visualizzazione dettagliata per quella categoria

**Requisiti Speciali**:
- I risultati devono essere visualizzati in modo chiaro e intuitivo
- I grafici comparativi devono essere accessibili e comprensibili
- La dashboard deve essere responsive per diversi dispositivi

**Frequenza**: Molto frequente (ogni analisi completata)

### UC6: Esplorare Dettagli

**Attori Principali**: Utente  
**Pre-condizioni**: I risultati dell'analisi sono stati visualizzati  
**Trigger**: L'utente desidera esplorare dettagli specifici  

**Scenario Principale di Successo**:
1. L'utente fa clic su una categoria specifica (Performance, SEO, Sicurezza, Tecnica)
2. Il sistema mostra una vista dettagliata con metriche specifiche per quella categoria
3. Il sistema visualizza grafici comparativi per le metriche della categoria
4. Il sistema evidenzia i punti di forza e debolezza di ciascun sito
5. L'utente può navigare tra le diverse categorie utilizzando i tab
6. L'utente può tornare alla vista generale dei risultati

**Estensioni**:
- 2a. Dati insufficienti per la categoria selezionata
  1. Il sistema mostra un messaggio che indica la mancanza di dati sufficienti
  2. Il sistema offre suggerimenti per analisi alternative

**Requisiti Speciali**:
- La navigazione tra categorie deve essere intuitiva
- I dettagli tecnici devono essere presentati in modo comprensibile
- Devono essere forniti suggerimenti per il miglioramento

**Frequenza**: Frequente (la maggior parte degli utenti esplora i dettagli)

### UC7: Esportare Risultati

**Attori Principali**: Utente  
**Pre-condizioni**: I risultati dell'analisi sono stati visualizzati  
**Trigger**: L'utente desidera salvare o condividere i risultati  

**Scenario Principale di Successo**:
1. L'utente fa clic sul pulsante "Esporta risultati"
2. Il sistema mostra un menu con opzioni di esportazione (CSV, PDF, Stampa)
3. L'utente seleziona il formato desiderato
4. Il sistema genera il file nel formato scelto
5. Il browser avvia il download del file o apre l'anteprima di stampa

**Estensioni**:
- 3a. L'utente sceglie l'opzione "Stampa"
  1. Il sistema prepara una versione ottimizzata per la stampa
  2. Il browser apre l'anteprima di stampa
- 4a. Errore nella generazione del file
  1. Il sistema mostra un messaggio di errore
  2. L'utente può riprovare o scegliere un formato alternativo

**Requisiti Speciali**:
- I file esportati devono includere tutti i dati rilevanti
- I formati di esportazione devono essere standard e compatibili
- La versione stampabile deve essere ottimizzata per la carta

**Frequenza**: Occasionale (alcuni utenti esportano i risultati)

### UC8: Analizzare Vincitore

**Attori Principali**: Utente  
**Pre-condizioni**: I risultati dell'analisi sono stati visualizzati  
**Trigger**: L'utente desidera comprendere i fattori che hanno determinato il vincitore  

**Scenario Principale di Successo**:
1. L'utente fa clic sul badge del vincitore o su un pulsante "Perché ha vinto?"
2. Il sistema mostra una spiegazione dettagliata dei fattori chiave che hanno contribuito alla vittoria
3. Il sistema evidenzia le principali differenze tra i due siti
4. Il sistema fornisce suggerimenti su come il sito perdente potrebbe migliorare
5. L'utente può navigare tra diverse aree di confronto
6. L'utente può tornare alla vista principale dei risultati

**Estensioni**:
- 2a. Il confronto è stato molto equilibrato
  1. Il sistema spiega i fattori di desempate utilizzati
  2. Il sistema mostra quanto è stato ravvicinato il confronto

**Requisiti Speciali**:
- Le spiegazioni devono essere comprensibili anche per utenti non tecnici
- I suggerimenti di miglioramento devono essere pratici e attuabili
- La visualizzazione deve evidenziare chiaramente i punti di forza e debolezza

**Frequenza**: Frequente (molti utenti vogliono capire il risultato)

## 5. Flussi di Interazione Principali

### 5.1 Flusso Base
1. L'utente inserisce gli URL dei due siti (UC1)
2. Il sistema valida gli URL (UC2)
3. Il sistema avvia l'analisi (UC3)
4. L'utente visualizza le animazioni durante l'analisi (UC4)
5. Il sistema mostra i risultati (UC5)
6. L'utente esplora i dettagli (UC6)
7. L'utente esporta i risultati (UC7)

### 5.2 Flusso Alternativo - Validazione Fallita
1. L'utente inserisce gli URL dei due siti (UC1)
2. Il sistema determina che i siti non sono confrontabili (UC2)
3. L'utente sceglie di procedere comunque
4. Il sistema avvia l'analisi con avviso (UC3)
5. Il flusso continua come nel flusso base

### 5.3 Flusso Alternativo - Analisi Parziale
1. L'utente inserisce gli URL dei due siti (UC1)
2. Il sistema valida gli URL (UC2)
3. Il sistema avvia l'analisi (UC3)
4. Alcune analisi esterne falliscono
5. Il sistema mostra risultati parziali con avviso (UC5)
6. L'utente può esplorare i dati disponibili (UC6)

## 6. Requisiti Non Funzionali dei Casi d'Uso

### 6.1 Performance
- UC3 (Avviare Analisi): Completamento entro 25 secondi per l'analisi completa
- UC4 (Visualizzare Animazioni): 60 fps per le animazioni, degradando su dispositivi meno potenti
- UC2 (Validare URL): Validazione completa entro 5 secondi

### 6.2 Usabilità
- UC1 (Inserire URL): Form semplice e intuitivo, accessibile da tastiera
- UC5 (Visualizzare Risultati): Visualizzazione chiara e comprensibile dei dati tecnici
- UC6 (Esplorare Dettagli): Navigazione intuitiva tra le categorie

### 6.3 Sicurezza
- UC2 (Validare URL): Sanitizzazione degli input per prevenire attacchi
- UC3 (Avviare Analisi): Protezione delle chiavi API dai client
- UC7 (Esportare Risultati): Prevenzione di data leakage nei file esportati

### 6.4 Scalabilità
- UC3 (Avviare Analisi): Gestione parallela di multiple richieste di analisi
- UC2 (Validare URL): Cache dei risultati di validazione per URL frequenti

## 7. Diagramma di Stato del Processo di Analisi

```
┌──────────────┐     ┌────────────┐     ┌──────────────┐     ┌──────────────┐
│              │     │            │     │              │     │              │
│  Iniziale    ├────>│  URL       ├────>│  Validazione ├────>│  Analisi     │
│              │     │  Inseriti  │     │              │     │  In Corso    │
│              │     │            │     │              │     │              │
└──────────────┘     └────────────┘     └──────┬───────┘     └──────┬───────┘
                                               │                    │
                                               │                    │
┌──────────────┐     ┌────────────┐           │                    │
│              │     │            │           │                    │
│  Nuova       │<────┤  Risultati │<──────────┴────────────────────┘
│  Analisi     │     │  Visualizzati│
│              │     │            │
└──────────────┘     └────────────┘
```

Il diagramma di stato mostra come il sistema passa attraverso diversi stati durante il processo di analisi:

1. **Iniziale**: Lo stato di partenza quando l'utente accede al sistema
2. **URL Inseriti**: L'utente ha inserito gli URL ma non ha ancora avviato l'analisi
3. **Validazione**: Il sistema sta verificando la validità e la pertinenza degli URL
4. **Analisi In Corso**: Il sistema sta eseguendo le analisi sui siti web
5. **Risultati Visualizzati**: Il sistema mostra i risultati all'utente
6. **Nuova Analisi**: L'utente decide di avviare una nuova analisi

Questo ciclo di stati rappresenta il core dell'esperienza utente di Site War.

## 8. Diagramma di Attività - Analisi Completa

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│  ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐       │
│  │          │     │          │     │          │     │          │       │
│  │ Inserire │────>│ Validare │────>│ Verificare│────>│ Avviare  │       │
│  │   URL    │     │   URL    │     │Pertinenza │     │ Analisi  │       │
│  │          │     │          │     │          │     │          │       │
│  └──────────┘     └──────────┘     └────┬─────┘     └────┬─────┘       │
│                                         │                │             │
│                                         │                │             │
│                                         ▼                ▼             │
│                                  ┌──────────┐     ┌──────────┐        │
│                                  │          │ No  │          │        │
│                                  │  È       │────>│ Mostrare │        │
│                                  │Pertinente│     │ Avviso   │        │
│                                  │          │     │          │        │
│                                  └────┬─────┘     └────┬─────┘        │
│                                       │ Sì              │             │
│                                       │                 │             │
│                                       ▼                 ▼             │
│                                  ┌──────────┐     ┌──────────┐        │
│                                  │          │     │          │        │
│                                  │ Eseguire │<────┤ Continua │        │
│                                  │ Analisi  │     │ Comunque │        │
│                                  │ Lato     │     │          │        │
│                                  │ Client   │     └──────────┘        │
│                                  │          │                         │
│                                  └────┬─────┘                         │
│                                       │                               │
│                                       │                               │
│                                       ▼                               │
│                                  ┌──────────┐                         │
│                                  │          │                         │
│                                  │ Eseguire │                         │
│                                  │ Analisi  │                         │
│                                  │ Lato     │                         │
│                                  │ Server   │                         │
│                                  │          │                         │
│                                  └────┬─────┘                         │
│                                       │                               │
│                                       │                               │
│                                       ▼                               │
│                                  ┌──────────┐                         │
│                                  │          │                         │
│                                  │ Combinare│                         │
│                                  │ Risultati│                         │
│                                  │          │                         │
│                                  └────┬─────┘                         │
│                                       │                               │
│                                       │                               │
│                                       ▼                               │
│                                  ┌──────────┐                         │
│                                  │          │                         │
│                                  │Determinare│                         │
│                                  │ Vincitore│                         │
│                                  │          │                         │
│                                  └────┬─────┘                         │
│                                       │                               │
│                                       │                               │
│                                       ▼                               │
│                                  ┌──────────┐                         │
│                                  │          │                         │
│                                  │ Mostrare │                         │
│                                  │ Risultati│                         │
│                                  │          │                         │
│                                  └──────────┘                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

Questo diagramma di attività illustra il flusso completo del processo di analisi, mostrando le varie decisioni e attività che si verificano durante l'esecuzione del sistema.