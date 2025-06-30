<?php
/**
 * SEOAnalyzer
 * 
 * Analizzatore specializzato per gli aspetti SEO di un sito web.
 * Valuta elementi come meta tag, struttura degli URL, heading e altri fattori
 * che influenzano l'ottimizzazione per i motori di ricerca.
 * 
 * Pattern implementati:
 * - Strategy
 * - Template Method
 */

require_once __DIR__ . '/BaseAnalyzer.php';
require_once __DIR__ . '/../../core/ServiceFactory.php';

class SEOAnalyzer extends BaseAnalyzer {
    /**
     * @var ServiceFactory Factory per servizi
     */
    private $serviceFactory;
    
    /**
     * @var array Contatori per elementi HTML
     */
    private $counters = [];
    
    /**
     * Costruttore
     * 
     * @param string $url URL del sito da analizzare
     * @param array $config Configurazione opzionale
     */
    public function __construct($url, $config = []) {
        parent::__construct($url, $config);
        $this->serviceFactory = new ServiceFactory();
    }
    
    /**
     * Esegue l'analisi SEO specifica
     */
    protected function doAnalyze() {
        // Analizza i meta tag
        $this->analyzeMeta();
        
        // Analizza la struttura degli heading
        $this->analyzeHeadings();
        
        // Analizza le immagini
        $this->analyzeImages();
        
        // Analizza i link
        $this->analyzeLinks();
        
        // Analizza il contenuto
        $this->analyzeContent();
        
        // Analizza i microdata e schema.org
        $this->analyzeStructuredData();
        
        // Analizza la velocità di caricamento
        $this->analyzeLoadSpeed();
        
        // Analizza URL e canonical
        $this->analyzeUrl();
        
        // Tenta di integrare dati da API esterne
        $this->integrateExternalData();
        
        // Calcola i punteggi finali
        $this->calculateScores();
    }
    
    /**
     * Analizza i meta tag SEO
     */
    private function analyzeMeta() {
        // Inizializza i risultati per i meta tag
        $this->results['metaTags'] = [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'robots' => null,
            'viewport' => null,
            'ogTags' => [],
            'twitterTags' => [],
            'count' => 0,
            'score' => 0
        ];
        
        // Estrae il titolo
        $titleNodes = $this->dom->getElementsByTagName('title');
        if ($titleNodes->length > 0) {
            $this->results['metaTags']['title'] = trim($titleNodes->item(0)->nodeValue);
            $this->results['metaTags']['titleLength'] = strlen($this->results['metaTags']['title']);
        }
        
        // Analizza i meta tag
        $metaTags = $this->dom->getElementsByTagName('meta');
        $this->results['metaTags']['count'] = $metaTags->length;
        
        foreach ($metaTags as $meta) {
            $name = $meta->getAttribute('name');
            $property = $meta->getAttribute('property');
            $content = $meta->getAttribute('content');
            
            // Meta name tags
            if (!empty($name) && !empty($content)) {
                switch (strtolower($name)) {
                    case 'description':
                        $this->results['metaTags']['description'] = $content;
                        $this->results['metaTags']['descriptionLength'] = strlen($content);
                        break;
                    case 'keywords':
                        $this->results['metaTags']['keywords'] = $content;
                        $this->results['metaTags']['keywordsCount'] = count(explode(',', $content));
                        break;
                    case 'robots':
                        $this->results['metaTags']['robots'] = $content;
                        break;
                    case 'viewport':
                        $this->results['metaTags']['viewport'] = $content;
                        break;
                }
            }
            
            // Open Graph tags
            if (!empty($property) && strpos($property, 'og:') === 0 && !empty($content)) {
                $ogProperty = substr($property, 3);
                $this->results['metaTags']['ogTags'][$ogProperty] = $content;
            }
            
            // Twitter Card tags
            if ((!empty($name) && strpos($name, 'twitter:') === 0 || !empty($property) && strpos($property, 'twitter:') === 0) && !empty($content)) {
                $twitterProperty = substr(!empty($name) ? $name : $property, 8);
                $this->results['metaTags']['twitterTags'][$twitterProperty] = $content;
            }
        }
        
        // Valuta i meta tag
        $score = 0;
        
        // Titolo: lunghezza ideale tra 50 e 60 caratteri
        if (isset($this->results['metaTags']['titleLength'])) {
            $titleLength = $this->results['metaTags']['titleLength'];
            if ($titleLength >= 40 && $titleLength <= 70) {
                $score += 10;
            } elseif ($titleLength > 0) {
                $score += 5;
            }
        }
        
        // Descrizione: lunghezza ideale tra 120 e 160 caratteri
        if (isset($this->results['metaTags']['descriptionLength'])) {
            $descLength = $this->results['metaTags']['descriptionLength'];
            if ($descLength >= 120 && $descLength <= 160) {
                $score += 10;
            } elseif ($descLength > 0) {
                $score += 5;
            }
        }
        
        // Keywords
        if (!empty($this->results['metaTags']['keywords'])) {
            $score += 2;
        }
        
        // Viewport
        if (!empty($this->results['metaTags']['viewport'])) {
            $score += 5;
        }
        
        // Open Graph
        $score += min(10, count($this->results['metaTags']['ogTags']) * 2);
        
        // Twitter Card
        $score += min(5, count($this->results['metaTags']['twitterTags']));
        
        // Normalizza il punteggio su 100
        $this->results['metaTags']['score'] = min(100, $score * 2);
    }
    
    /**
     * Analizza la struttura degli heading
     */
    private function analyzeHeadings() {
        // Inizializza i risultati per gli heading
        $this->results['headings'] = [
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
            'count' => 0,
            'score' => 0
        ];
        
        // Analizza ogni livello di heading
        for ($i = 1; $i <= 6; $i++) {
            $headings = $this->dom->getElementsByTagName('h' . $i);
            
            $this->results['headings']['h' . $i] = [];
            $this->results['headings']['h' . $i . 'Count'] = $headings->length;
            
            foreach ($headings as $heading) {
                $this->results['headings']['h' . $i][] = trim($heading->nodeValue);
            }
            
            $this->results['headings']['count'] += $headings->length;
        }
        
        // Valuta la struttura degli heading
        $score = 0;
        
        // È presente almeno un H1
        if ($this->results['headings']['h1Count'] > 0) {
            $score += 15;
            
            // Penalità per troppi H1
            if ($this->results['headings']['h1Count'] > 1) {
                $score -= ($this->results['headings']['h1Count'] - 1) * 5;
            }
        }
        
        // Presenza di H2
        if ($this->results['headings']['h2Count'] > 0) {
            $score += min(15, $this->results['headings']['h2Count'] * 3);
        }
        
        // Presenza di H3
        if ($this->results['headings']['h3Count'] > 0) {
            $score += min(10, $this->results['headings']['h3Count'] * 2);
        }
        
        // Gerarchia dei titoli
        if ($this->results['headings']['h1Count'] > 0 && 
            $this->results['headings']['h2Count'] > 0 && 
            $this->results['headings']['h3Count'] > 0) {
            $score += 10;
        }
        
        // Normalizza il punteggio su 100
        $this->results['headings']['score'] = min(100, max(0, $score * 2));
    }
    
    /**
     * Analizza le immagini
     */
    private function analyzeImages() {
        // Inizializza i risultati per le immagini
        $this->results['images'] = [
            'total' => 0,
            'withAlt' => 0,
            'withTitle' => 0,
            'score' => 0
        ];
        
        // Estrae tutte le immagini
        $images = $this->dom->getElementsByTagName('img');
        $this->results['images']['total'] = $images->length;
        
        foreach ($images as $img) {
            if ($img->hasAttribute('alt') && !empty($img->getAttribute('alt'))) {
                $this->results['images']['withAlt']++;
            }
            
            if ($img->hasAttribute('title') && !empty($img->getAttribute('title'))) {
                $this->results['images']['withTitle']++;
            }
        }
        
        // Calcola le percentuali
        if ($this->results['images']['total'] > 0) {
            $this->results['images']['altPercentage'] = round(($this->results['images']['withAlt'] / $this->results['images']['total']) * 100);
            $this->results['images']['titlePercentage'] = round(($this->results['images']['withTitle'] / $this->results['images']['total']) * 100);
        } else {
            $this->results['images']['altPercentage'] = 0;
            $this->results['images']['titlePercentage'] = 0;
        }
        
        // Valuta le immagini
        if ($this->results['images']['total'] > 0) {
            $score = ($this->results['images']['altPercentage'] * 0.8) + ($this->results['images']['titlePercentage'] * 0.2);
        } else {
            // Se non ci sono immagini, assegna un punteggio neutro
            $score = 50;
        }
        
        $this->results['images']['score'] = min(100, max(0, $score));
    }
    
    /**
     * Analizza i link
     */
    private function analyzeLinks() {
        // Inizializza i risultati per i link
        $this->results['links'] = [
            'total' => 0,
            'internal' => 0,
            'external' => 0,
            'withTitle' => 0,
            'nofollow' => 0,
            'score' => 0
        ];
        
        // Estrae tutti i link
        $links = $this->dom->getElementsByTagName('a');
        $this->results['links']['total'] = $links->length;
        
        $domain = parse_url($this->url, PHP_URL_HOST);
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            if (empty($href) || $href === '#' || strpos($href, 'javascript:') === 0) {
                continue; // Ignora link vuoti o javascript
            }
            
            // Determina se il link è interno o esterno
            $linkDomain = parse_url($href, PHP_URL_HOST);
            
            if (empty($linkDomain) || $linkDomain === $domain) {
                $this->results['links']['internal']++;
            } else {
                $this->results['links']['external']++;
            }
            
            // Verifica attributi
            if ($link->hasAttribute('title') && !empty($link->getAttribute('title'))) {
                $this->results['links']['withTitle']++;
            }
            
            if ($link->hasAttribute('rel') && strpos($link->getAttribute('rel'), 'nofollow') !== false) {
                $this->results['links']['nofollow']++;
            }
        }
        
        // Calcola le percentuali
        if ($this->results['links']['total'] > 0) {
            $this->results['links']['titlePercentage'] = round(($this->results['links']['withTitle'] / $this->results['links']['total']) * 100);
            $this->results['links']['internalPercentage'] = round(($this->results['links']['internal'] / $this->results['links']['total']) * 100);
        } else {
            $this->results['links']['titlePercentage'] = 0;
            $this->results['links']['internalPercentage'] = 0;
        }
        
        // Valuta i link
        $score = 50; // Punteggio base
        
        // Un buon mix di link interni ed esterni
        if ($this->results['links']['total'] > 0) {
            // Presenza di alcuni link esterni è positiva, ma non troppi
            $externalRatio = $this->results['links']['external'] / $this->results['links']['total'];
            if ($externalRatio > 0 && $externalRatio < 0.3) {
                $score += 25;
            } else if ($externalRatio >= 0.3 && $externalRatio < 0.5) {
                $score += 15;
            } else if ($externalRatio >= 0.5) {
                $score -= 10;
            }
            
            // Link con title
            $score += min(15, round($this->results['links']['titlePercentage'] / 10));
            
            // Corretto uso di nofollow per link esterni
            if ($this->results['links']['external'] > 0 && $this->results['links']['nofollow'] > 0) {
                $score += 10;
            }
        }
        
        $this->results['links']['score'] = min(100, max(0, $score));
    }
    
    /**
     * Analizza il contenuto
     */
    private function analyzeContent() {
        // Inizializza i risultati per il contenuto
        $this->results['content'] = [
            'textLength' => 0,
            'wordCount' => 0,
            'paragraphCount' => 0,
            'score' => 0
        ];
        
        // Estrae il testo principale
        $bodyText = '';
        $body = $this->dom->getElementsByTagName('body');
        
        if ($body->length > 0) {
            $bodyText = $body->item(0)->textContent;
        }
        
        // Rimuove spazi in eccesso
        $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));
        
        // Calcola le metriche
        $this->results['content']['textLength'] = strlen($cleanText);
        $this->results['content']['wordCount'] = str_word_count($cleanText);
        
        // Conta i paragrafi
        $paragraphs = $this->dom->getElementsByTagName('p');
        $this->results['content']['paragraphCount'] = $paragraphs->length;
        
        // Valuta il contenuto
        $score = 0;
        
        // Lunghezza del contenuto (minimo 300 parole per un buon contenuto SEO)
        if ($this->results['content']['wordCount'] >= 300) {
            $score += min(50, $this->results['content']['wordCount'] / 20);
        } else {
            $score += min(30, $this->results['content']['wordCount'] / 10);
        }
        
        // Numero di paragrafi (indica struttura del contenuto)
        if ($this->results['content']['paragraphCount'] > 0) {
            $score += min(25, $this->results['content']['paragraphCount'] * 2);
        }
        
        // Densità di parole (evita paragrafi troppo lunghi)
        if ($this->results['content']['paragraphCount'] > 0) {
            $wordDensity = $this->results['content']['wordCount'] / $this->results['content']['paragraphCount'];
            if ($wordDensity >= 15 && $wordDensity <= 40) {
                $score += 25;
            } else if ($wordDensity > 0) {
                $score += 10;
            }
        }
        
        $this->results['content']['score'] = min(100, max(0, $score));
    }
    
    /**
     * Analizza i dati strutturati (microdata, schema.org)
     */
    private function analyzeStructuredData() {
        // Inizializza i risultati per i dati strutturati
        $this->results['structuredData'] = [
            'detected' => false,
            'types' => [],
            'score' => 0
        ];
        
        // Cerca elementi con attributi schema.org
        $itemScope = 0;
        $itemType = 0;
        
        // Conta gli elementi con attributi schema.org
        $xpath = new DOMXPath($this->dom);
        
        // Cerca itemscope
        $itemScopeNodes = $xpath->query('//*[@itemscope]');
        $itemScope = $itemScopeNodes->length;
        
        // Cerca itemtype
        $itemTypeNodes = $xpath->query('//*[@itemtype]');
        $itemType = $itemTypeNodes->length;
        
        // Estrae i tipi di schema
        foreach ($itemTypeNodes as $node) {
            $type = $node->getAttribute('itemtype');
            if (strpos($type, 'schema.org/') !== false) {
                $schemaType = substr($type, strrpos($type, '/') + 1);
                if (!in_array($schemaType, $this->results['structuredData']['types'])) {
                    $this->results['structuredData']['types'][] = $schemaType;
                }
            }
        }
        
        // Cerca script di tipo application/ld+json
        $jsonLdScripts = $xpath->query('//script[@type="application/ld+json"]');
        $jsonLdCount = $jsonLdScripts->length;
        
        // Estrae i tipi dai JSON-LD
        foreach ($jsonLdScripts as $script) {
            $jsonContent = $script->nodeValue;
            
            // Tenta di decodificare il JSON
            $jsonData = json_decode($jsonContent, true);
            
            if ($jsonData && isset($jsonData['@type'])) {
                if (!in_array($jsonData['@type'], $this->results['structuredData']['types'])) {
                    $this->results['structuredData']['types'][] = $jsonData['@type'];
                }
            }
        }
        
        // Determina se sono stati rilevati dati strutturati
        $this->results['structuredData']['detected'] = ($itemScope > 0 || $itemType > 0 || $jsonLdCount > 0);
        
        // Memorizza i conteggi
        $this->results['structuredData']['itemScope'] = $itemScope;
        $this->results['structuredData']['itemType'] = $itemType;
        $this->results['structuredData']['jsonLd'] = $jsonLdCount;
        
        // Valuta i dati strutturati
        $score = 0;
        
        // Presenza di dati strutturati
        if ($this->results['structuredData']['detected']) {
            $score += 50;
            
            // Bonus per JSON-LD (formato preferito)
            if ($jsonLdCount > 0) {
                $score += 30;
            }
            
            // Bonus per varietà di tipi
            $score += min(20, count($this->results['structuredData']['types']) * 5);
        }
        
        $this->results['structuredData']['score'] = min(100, $score);
    }
    
    /**
     * Analizza la velocità di caricamento
     */
    private function analyzeLoadSpeed() {
        // Inizializza i risultati per la velocità di caricamento
        $this->results['loadSpeed'] = [
            'time' => 0,
            'score' => 0
        ];
        
        // Ottieni il tempo di caricamento dalla richiesta cURL
        $loadTime = curl_getinfo($this->curlHandle, CURLINFO_TOTAL_TIME);
        $this->results['loadSpeed']['time'] = round($loadTime, 3);
        
        // Valuta la velocità di caricamento
        if ($loadTime <= 1) {
            $this->results['loadSpeed']['score'] = 100;
        } elseif ($loadTime <= 2) {
            $this->results['loadSpeed']['score'] = 85;
        } elseif ($loadTime <= 3) {
            $this->results['loadSpeed']['score'] = 70;
        } elseif ($loadTime <= 5) {
            $this->results['loadSpeed']['score'] = 50;
        } else {
            $this->results['loadSpeed']['score'] = max(0, 100 - ($loadTime * 10));
        }
    }
    
    /**
     * Analizza l'URL e i tag canonical
     */
    private function analyzeUrl() {
        // Inizializza i risultati per l'URL
        $this->results['url'] = [
            'format' => null,
            'canonical' => null,
            'canonicalCorrect' => false,
            'score' => 0
        ];
        
        // Analizza l'URL
        $parsedUrl = parse_url($this->url);
        
        // Verifica se l'URL è in formato SEO-friendly
        $path = $parsedUrl['path'] ?? '/';
        $this->results['url']['format'] = $path;
        
        // URL SEO-friendly: non contiene parametri di query, ha parole separate da trattini
        $seoFriendly = true;
        
        if (isset($parsedUrl['query'])) {
            $seoFriendly = false;
        }
        
        if (strpos($path, '_') !== false) {
            $seoFriendly = false;
        }
        
        $this->results['url']['seoFriendly'] = $seoFriendly;
        
        // Cerca il tag canonical
        $xpath = new DOMXPath($this->dom);
        $canonicalNodes = $xpath->query('//link[@rel="canonical"]');
        
        if ($canonicalNodes->length > 0) {
            $canonicalUrl = $canonicalNodes->item(0)->getAttribute('href');
            $this->results['url']['canonical'] = $canonicalUrl;
            
            // Verifica se l'URL canonical è corretto
            $actualUrl = $this->getFullUrl($this->url);
            $canonicalFull = $this->getFullUrl($canonicalUrl);
            
            $this->results['url']['canonicalCorrect'] = ($actualUrl === $canonicalFull);
        }
        
        // Valuta l'URL
        $score = 0;
        
        // URL SEO-friendly
        if ($seoFriendly) {
            $score += 50;
        }
        
        // Presenza del tag canonical
        if (!empty($this->results['url']['canonical'])) {
            $score += 30;
            
            // Canonical corretto
            if ($this->results['url']['canonicalCorrect']) {
                $score += 20;
            }
        }
        
        $this->results['url']['score'] = min(100, $score);
    }
    
    /**
     * Integra dati da API esterne
     */
    private function integrateExternalData() {
        // Inizializza il container per dati esterni
        if (!isset($this->results['external'])) {
            $this->results['external'] = [
                'domain_authority' => null,
                'page_authority' => null,
                'backlinks' => 0,
                'creation_date' => null,
                'expiration_date' => null,
                'registrar' => null
            ];
        }
        
        // Ottieni il dominio dall'URL
        $domain = parse_url($this->url, PHP_URL_HOST);
        
        // 1. Integrazione dati da Moz API
        $this->integrateMozData($domain);
        
        // 2. Integrazione dati da WHOIS API
        $this->integrateWhoisData($domain);
    }
    
    /**
     * Integra dati da Moz API
     * 
     * @param string $domain Il dominio da analizzare
     */
    private function integrateMozData($domain) {
        try {
            error_log("[SEO] Avvio integrazione dati esterni da Moz API per dominio: {$domain}");
            
            // Creiamo un nuovo array di configurazione per Moz API v2
            $proxyConfig = [
                'service' => 'moz',
                'timeout' => 5,
                'data' => json_encode([
                    'targets' => ["https://{$domain}"],
                    'cols' => 'domain_authority,page_authority,external_links'
                ]),
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];
            
            // Creiamo un nuovo servizio con la configurazione completa
            $proxyService = $this->serviceFactory->createService('proxy', $proxyConfig);
            
            error_log("[SEO] Esecuzione chiamata a Moz API");
            $success = $proxyService->execute();
            
            if ($success && !$proxyService->hasError()) {
                error_log("[SEO] Chiamata a Moz API completata con successo");
                $mozData = $proxyService->getResult();
                
                if (is_array($mozData)) {
                    error_log("[SEO] Dati Moz API integrati: " . json_encode($mozData));
                    
                    // Aggiorna solo i campi specifici che ci servono
                    $this->results['external']['domain_authority'] = $mozData['domain_authority'] ?? $this->results['external']['domain_authority'];
                    $this->results['external']['page_authority'] = $mozData['page_authority'] ?? $this->results['external']['page_authority'];
                    $this->results['external']['backlinks'] = $mozData['backlinks'] ?? $this->results['external']['backlinks'];
                }
            } else {
                error_log("[SEO ERROR] Errore chiamata Moz API: " . $proxyService->getErrorMessage());
            }
        } catch (Exception $e) {
            // In caso di errore, continua senza dati Moz
            error_log("[SEO ERROR] Eccezione durante l'integrazione dati Moz: " . $e->getMessage());
        }
    }
    
    /**
     * Integra dati da WHOIS API
     * 
     * @param string $domain Il dominio da analizzare
     */
    private function integrateWhoisData($domain) {
        try {
            error_log("[SEO] Avvio integrazione dati WHOIS per dominio: {$domain}");
            
            // Configurazione per WHOIS API
            $proxyConfig = [
                'service' => 'whois',
                'timeout' => 5,
                'params' => [
                    'domainName' => $domain
                ],
                'method' => 'GET'
            ];
            
            // Creiamo un nuovo servizio con la configurazione
            $proxyService = $this->serviceFactory->createService('proxy', $proxyConfig);
            
            error_log("[SEO] Esecuzione chiamata a WHOIS API");
            $success = $proxyService->execute();
            
            if ($success && !$proxyService->hasError()) {
                error_log("[SEO] Chiamata a WHOIS API completata con successo");
                $whoisData = $proxyService->getResult();
                
                if (is_array($whoisData)) {
                    error_log("[SEO] Dati WHOIS integrati: " . json_encode($whoisData));
                    
                    // Aggiorna i campi specifici
                    $this->results['external']['creation_date'] = $whoisData['creation_date'] ?? $this->results['external']['creation_date'];
                    $this->results['external']['expiration_date'] = $whoisData['expiration_date'] ?? $this->results['external']['expiration_date'];
                    $this->results['external']['registrar'] = $whoisData['registrar'] ?? $this->results['external']['registrar'];
                }
            } else {
                error_log("[SEO ERROR] Errore chiamata WHOIS API: " . $proxyService->getErrorMessage());
            }
        } catch (Exception $e) {
            // In caso di errore, continua senza dati WHOIS
            error_log("[SEO ERROR] Eccezione durante l'integrazione dati WHOIS: " . $e->getMessage());
        }
    }
    
    /**
     * Calcola i punteggi finali
     */
    private function calculateScores() {
        // Pesi per le diverse categorie
        $weights = [
            'metaTags' => 0.20,
            'headings' => 0.15,
            'images' => 0.10,
            'links' => 0.10,
            'content' => 0.15,
            'structuredData' => 0.10,
            'loadSpeed' => 0.10,
            'url' => 0.10
        ];
        
        // Calcola il punteggio totale
        $totalScore = 0;
        
        foreach ($weights as $category => $weight) {
            if (isset($this->results[$category]['score'])) {
                $totalScore += $this->results[$category]['score'] * $weight;
            }
        }
        
        // Arrotonda il punteggio
        $this->results['totalScore'] = round($totalScore, 2);
    }
    
    /**
     * Ottiene l'URL completo a partire da un URL relativo o assoluto
     * 
     * @param string $url URL relativo o assoluto
     * @return string URL completo
     */
    private function getFullUrl($url) {
        // Se l'URL è già completo, restituiscilo
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        // Se l'URL è relativo, combinalo con l'URL base
        $parsedBase = parse_url($this->url);
        $base = $parsedBase['scheme'] . '://' . $parsedBase['host'];
        
        // Se l'URL inizia con /, è relativo alla radice
        if (strpos($url, '/') === 0) {
            return $base . $url;
        }
        
        // Altrimenti è relativo al percorso corrente
        $path = $parsedBase['path'] ?? '/';
        $directory = dirname($path);
        
        if ($directory !== '/') {
            $directory .= '/';
        }
        
        return $base . $directory . $url;
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return bool True se il fallback ha avuto successo
     */
    protected function implementFallback() {
        // In caso di errore, crea risultati di base
        $this->results = [
            'metaTags' => ['score' => 0],
            'headings' => ['score' => 0],
            'images' => ['score' => 0],
            'links' => ['score' => 0],
            'content' => ['score' => 0],
            'structuredData' => ['score' => 0],
            'loadSpeed' => ['score' => 0],
            'url' => ['score' => 0],
            'totalScore' => 0,
            'error' => $this->errorMessage
        ];
        
        return true;
    }
}