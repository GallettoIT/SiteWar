<?php
/**
 * Script per catturare e analizzare le risposte reali delle API esterne
 * 
 * Questo script salva un campione delle risposte di ogni API in formato JSON,
 * permettendo di studiare la struttura reale dei dati restituiti.
 */

require_once __DIR__ . '/server/services/ProxyService.php';
require_once __DIR__ . '/server/core/ServiceFactory.php';

// Funzione per salvare una risposta API in un file JSON
function saveApiResponse($serviceName, $response) {
    $filename = __DIR__ . "/api_samples/{$serviceName}_response.json";
    
    // Crea la directory se non esiste
    if (!is_dir(__DIR__ . "/api_samples")) {
        mkdir(__DIR__ . "/api_samples", 0755, true);
    }
    
    // Salva la risposta in formato JSON pretty-printed
    file_put_contents(
        $filename, 
        json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    
    echo "Risposta API {$serviceName} salvata in: {$filename}\n";
}

// Funzione per stampare la struttura di una risposta API
function analyzeApiStructure($serviceName, $response) {
    echo "\n==========================================\n";
    echo "== Analisi struttura risposta {$serviceName} ==\n";
    echo "==========================================\n";
    
    // Primi livelli della risposta
    echo "Chiavi di primo livello:\n";
    if (is_array($response)) {
        foreach (array_keys($response) as $key) {
            echo "- {$key}\n";
        }
    } else {
        echo "La risposta non è un array\n";
    }
    
    // Analisi più dettagliata per specifiche API
    switch ($serviceName) {
        case 'moz':
            if (isset($response['results']) && is_array($response['results']) && !empty($response['results'])) {
                echo "\nStruttura 'results[0]':\n";
                foreach (array_keys($response['results'][0]) as $key) {
                    echo "- {$key}\n";
                }
            }
            break;
            
        case 'whois':
            if (isset($response['WhoisRecord'])) {
                echo "\nStruttura 'WhoisRecord':\n";
                foreach (array_keys($response['WhoisRecord']) as $key) {
                    echo "- {$key}\n";
                }
                
                // Controlla se c'è anche registryData
                if (isset($response['WhoisRecord']['registryData'])) {
                    echo "\nStruttura 'WhoisRecord.registryData':\n";
                    foreach (array_keys($response['WhoisRecord']['registryData']) as $key) {
                        echo "- {$key}\n";
                    }
                }
            }
            break;
    }
    
    echo "\nProposta di mappatura dei dati:\n";
    echo "--------------------------------\n";
    
    // Mostra una proposta di mappatura in base al servizio
    switch ($serviceName) {
        case 'moz':
            echo "Autoritá dominio: response";
            if (isset($response['results'])) echo "['results'][0]";
            echo "['domain_authority']\n";
            
            echo "Autoritá pagina: response";
            if (isset($response['results'])) echo "['results'][0]";
            echo "['page_authority']\n";
            
            echo "Backlinks esterni: response";
            if (isset($response['results'])) echo "['results'][0]";
            echo "['external_links'] o ['linking_domains']\n";
            break;
            
        case 'whois':
            echo "Data creazione: response";
            if (isset($response['WhoisRecord'])) {
                echo "['WhoisRecord']";
                if (isset($response['WhoisRecord']['createdDate'])) {
                    echo "['createdDate']\n";
                } elseif (isset($response['WhoisRecord']['registryData']) && 
                         isset($response['WhoisRecord']['registryData']['createdDate'])) {
                    echo "['registryData']['createdDate']\n";
                } else {
                    echo " - campo non trovato, cercare in altre sottochiavi\n";
                }
            } else {
                echo " - struttura WhoisRecord non trovata\n";
            }
            
            echo "Data scadenza: response";
            if (isset($response['WhoisRecord'])) {
                echo "['WhoisRecord']";
                if (isset($response['WhoisRecord']['expiresDate'])) {
                    echo "['expiresDate']\n";
                } elseif (isset($response['WhoisRecord']['registryData']) && 
                         isset($response['WhoisRecord']['registryData']['expiresDate'])) {
                    echo "['registryData']['expiresDate']\n";
                } else {
                    echo " - campo non trovato, cercare in altre sottochiavi\n";
                }
            } else {
                echo " - struttura WhoisRecord non trovata\n";
            }
            
            echo "Registrar: response";
            if (isset($response['WhoisRecord'])) {
                echo "['WhoisRecord']";
                if (isset($response['WhoisRecord']['registrarName'])) {
                    echo "['registrarName']\n";
                } elseif (isset($response['WhoisRecord']['registryData']) && 
                         isset($response['WhoisRecord']['registryData']['registrarName'])) {
                    echo "['registryData']['registrarName']\n";
                } else {
                    echo " - campo non trovato, cercare in altre sottochiavi\n";
                }
            } else {
                echo " - struttura WhoisRecord non trovata\n";
            }
            break;
    }
    
    echo "\n";
}

// URLs di test
$testUrls = [
    "https://www.unipr.it",
    "https://www.unimore.it"
];

// Crea il factory
$factory = new ServiceFactory();

echo "\n\nINIZIO ANALISI RISPOSTE API\n\n";

foreach ($testUrls as $testUrl) {
    $domain = parse_url($testUrl, PHP_URL_HOST);
    echo "Analisi per dominio: {$domain}\n";
    
    // Test Moz API
    try {
        echo "\nTest Moz API per {$domain}...\n";
        
        $proxyConfig = [
            'service' => 'moz',
            'timeout' => 15,
            'data' => json_encode([
                'targets' => ["https://{$domain}"],
                'cols' => 'domain_authority,page_authority,external_links'
            ]),
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];
        
        $proxyService = $factory->createService('proxy', $proxyConfig);
        $success = $proxyService->execute();
        
        if ($success && !$proxyService->hasError()) {
            $result = $proxyService->getResult();
            
            // Ottieni la risposta originale prima dell'elaborazione
            $originalResponse = $proxyService->getRawResponse();
            
            // Salva e analizza
            if ($originalResponse) {
                saveApiResponse("moz_{$domain}", $originalResponse);
                analyzeApiStructure("moz", $originalResponse);
            }
        } else {
            echo "Errore Moz API: " . $proxyService->getErrorMessage() . "\n";
        }
    } catch (Exception $e) {
        echo "Eccezione Moz API: " . $e->getMessage() . "\n";
    }
    
    // Test WHOIS API
    try {
        echo "\nTest WHOIS API per {$domain}...\n";
        
        $proxyConfig = [
            'service' => 'whois',
            'timeout' => 15,
            'params' => [
                'domainName' => $domain
            ],
            'method' => 'GET'
        ];
        
        $proxyService = $factory->createService('proxy', $proxyConfig);
        $success = $proxyService->execute();
        
        if ($success && !$proxyService->hasError()) {
            $result = $proxyService->getResult();
            
            // Ottieni la risposta originale prima dell'elaborazione
            $originalResponse = $proxyService->getRawResponse();
            
            // Salva e analizza
            if ($originalResponse) {
                saveApiResponse("whois_{$domain}", $originalResponse);
                analyzeApiStructure("whois", $originalResponse);
            }
        } else {
            echo "Errore WHOIS API: " . $proxyService->getErrorMessage() . "\n";
        }
    } catch (Exception $e) {
        echo "Eccezione WHOIS API: " . $e->getMessage() . "\n";
    }
}

echo "\n\nFINE ANALISI RISPOSTE API\n\n";