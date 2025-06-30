<?php
/**
 * PerformanceAnalyzer
 * 
 * Analizzatore specializzato per gli aspetti di performance di un sito web.
 * Integra Google PageSpeed API per valutare la velocità di caricamento,
 * le metriche Core Web Vitals e altre metriche di performance.
 * 
 * Pattern implementati:
 * - Strategy
 * - Template Method
 * - Adapter (per l'integrazione con Google PageSpeed API)
 */

require_once __DIR__ . '/BaseAnalyzer.php';
require_once __DIR__ . '/../../core/ServiceFactory.php';
require_once __DIR__ . '/../../utils/Cache.php';

class PerformanceAnalyzer extends BaseAnalyzer {
    /**
     * @var ServiceFactory Factory per servizi
     */
    private $serviceFactory;
    
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * @var array Dati PageSpeed
     */
    private $pageSpeedData;
    
    /**
     * @var array Metriche di performance locali
     */
    private $localMetrics;
    
    /**
     * @var array Metriche Core Web Vitals
     */
    private $webVitals;
    
    /**
     * @var int Timeout per PageSpeed API in secondi
     */
    private $apiTimeout = 6;
    
    /**
     * Costruttore
     * 
     * @param string $url URL del sito da analizzare
     * @param array $config Configurazione opzionale
     */
    public function __construct($url, $config = []) {
        parent::__construct($url, $config);
        $this->serviceFactory = new ServiceFactory();
        $this->cache = new Cache();
        
        // Override del timeout API se specificato
        if (isset($config['apiTimeout'])) {
            $this->apiTimeout = $config['apiTimeout'];
        }
    }
    
    /**
     * Esegue l'analisi delle performance specifica
     */
    protected function doAnalyze() {
        // Inizializza array per i risultati delle metriche
        $this->localMetrics = [];
        $this->webVitals = [];
        $this->pageSpeedData = [];
        
        // Analizza le metriche di performance in locale
        $this->analyzeLocalPerformance();
        
        // Recupera e analizza dati da PageSpeed API
        $this->fetchPageSpeedData();
        
        // Analizza i resource hints
        $this->analyzeResourceHints();
        
        // Analizza elementi che bloccano il rendering
        $this->analyzeRenderBlocking();
        
        // Analizza le ottimizzazioni delle immagini
        $this->analyzeImageOptimization();
        
        // Analizza le ottimizzazioni del caching
        $this->analyzeCaching();
        
        // Calcola i punteggi finali
        $this->calculateScores();
    }
    
    /**
     * Analizza le metriche di performance locali
     */
    private function analyzeLocalPerformance() {
        // Inizializza i risultati per le metriche locali
        $this->results['localMetrics'] = [
            'timeToFirstByte' => 0,
            'loadTime' => 0,
            'domSize' => 0,
            'resourceCount' => 0,
            'score' => 0
        ];
        
        // Ottieni il TTFB (Time To First Byte) dalla richiesta cURL
        $ttfb = curl_getinfo($this->curlHandle, CURLINFO_STARTTRANSFER_TIME);
        $this->results['localMetrics']['timeToFirstByte'] = round($ttfb * 1000); // converti in ms
        
        // Ottieni il tempo di caricamento totale
        $loadTime = curl_getinfo($this->curlHandle, CURLINFO_TOTAL_TIME);
        $this->results['localMetrics']['loadTime'] = round($loadTime * 1000); // converti in ms
        
        // Ottieni la dimensione della risposta
        $size = curl_getinfo($this->curlHandle, CURLINFO_SIZE_DOWNLOAD);
        $this->results['localMetrics']['pageSize'] = $size;
        
        // Analizza la dimensione del DOM
        if ($this->dom) {
            $elements = $this->dom->getElementsByTagName('*');
            $this->results['localMetrics']['domSize'] = $elements->length;
        }
        
        // Conta le risorse
        $this->countResources();
        
        // Valuta le metriche locali
        $this->evaluateLocalMetrics();
    }
    
    /**
     * Conta le risorse richieste dalla pagina
     */
    private function countResources() {
        $resourceCount = 0;
        $resourceTypes = [
            'scripts' => 0,
            'styles' => 0,
            'images' => 0,
            'fonts' => 0,
            'iframes' => 0,
            'other' => 0
        ];
        
        // Conta gli script
        $scripts = $this->dom->getElementsByTagName('script');
        $resourceTypes['scripts'] = $scripts->length;
        $resourceCount += $scripts->length;
        
        // Conta i fogli di stile
        $styles = $this->dom->getElementsByTagName('link');
        foreach ($styles as $style) {
            if ($style->getAttribute('rel') === 'stylesheet') {
                $resourceTypes['styles']++;
                $resourceCount++;
            } else if ($style->getAttribute('rel') === 'preload' && $style->getAttribute('as') === 'font') {
                $resourceTypes['fonts']++;
                $resourceCount++;
            }
        }
        
        // Conta le immagini
        $images = $this->dom->getElementsByTagName('img');
        $resourceTypes['images'] = $images->length;
        $resourceCount += $images->length;
        
        // Conta i font (approssimazione tramite @font-face nei tag style)
        $styleElements = $this->dom->getElementsByTagName('style');
        foreach ($styleElements as $styleElement) {
            $content = $styleElement->nodeValue;
            preg_match_all('/@font-face/i', $content, $matches);
            $resourceTypes['fonts'] += count($matches[0]);
            $resourceCount += count($matches[0]);
        }
        
        // Conta gli iframe
        $iframes = $this->dom->getElementsByTagName('iframe');
        $resourceTypes['iframes'] = $iframes->length;
        $resourceCount += $iframes->length;
        
        // Memo i risultati
        $this->results['localMetrics']['resourceCount'] = $resourceCount;
        $this->results['localMetrics']['resourceBreakdown'] = $resourceTypes;
    }
    
    /**
     * Valuta le metriche di performance locali
     */
    private function evaluateLocalMetrics() {
        $score = 100; // Punteggio iniziale massimo
        
        // Valuta TTFB (Time To First Byte)
        $ttfb = $this->results['localMetrics']['timeToFirstByte'];
        if ($ttfb <= 100) { // Eccellente
            $score -= 0;
        } else if ($ttfb <= 300) { // Buono
            $score -= 5;
        } else if ($ttfb <= 600) { // Nella media
            $score -= 15;
        } else if ($ttfb <= 1000) { // Lento
            $score -= 30;
        } else { // Molto lento
            $score -= 40;
        }
        
        // Valuta tempo di caricamento totale
        $loadTime = $this->results['localMetrics']['loadTime'];
        if ($loadTime <= 1000) { // Eccellente
            $score -= 0;
        } else if ($loadTime <= 2500) { // Buono
            $score -= 5;
        } else if ($loadTime <= 4000) { // Nella media
            $score -= 15;
        } else if ($loadTime <= 6000) { // Lento
            $score -= 25;
        } else { // Molto lento
            $score -= 35;
        }
        
        // Valuta dimensione DOM
        $domSize = $this->results['localMetrics']['domSize'];
        if ($domSize <= 500) { // Ottimale
            $score -= 0;
        } else if ($domSize <= 1000) { // Buono
            $score -= 5;
        } else if ($domSize <= 2000) { // Nella media
            $score -= 10;
        } else if ($domSize <= 3000) { // Grande
            $score -= 15;
        } else { // Molto grande
            $score -= 25;
        }
        
        // Valuta numero di risorse
        $resourceCount = $this->results['localMetrics']['resourceCount'];
        if ($resourceCount <= 20) { // Ottimale
            $score -= 0;
        } else if ($resourceCount <= 40) { // Buono
            $score -= 5;
        } else if ($resourceCount <= 60) { // Nella media
            $score -= 10;
        } else if ($resourceCount <= 80) { // Molte
            $score -= 15;
        } else { // Troppe
            $score -= 25;
        }
        
        // Assicura che il punteggio non sia negativo
        $this->results['localMetrics']['score'] = max(0, $score);
    }
    
    /**
     * Recupera e analizza i dati da Google PageSpeed API
     */
    private function fetchPageSpeedData() {
        // Inizializza i risultati per PageSpeed
        $this->results['pageSpeed'] = [
            'score' => 0,
            'dataAvailable' => false,
            'metrics' => [],
            'opportunities' => [],
            'diagnostics' => []
        ];
        
        // Chiave di cache basata sull'URL
        $cacheKey = 'pagespeed_' . md5($this->url);
        
        // Controlla se i dati sono in cache
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData) {
            $this->pageSpeedData = $cachedData;
            $this->processPageSpeedData();
            return;
        }
        
        try {
            error_log("[PAGESPEED] Avvio analisi PageSpeed per URL: {$this->url}");
            
            // Utilizza il servizio di proxy per chiamare Google PageSpeed API
            $proxyService = $this->serviceFactory->createService('proxy', [
                'service' => 'pagespeed',
                'timeout' => $this->apiTimeout,
                'api_key_in_url' => true,
                'api_key_param' => 'key',
                'params' => [
                    'url' => $this->url,
                    'strategy' => 'mobile', // Analisi mobile-first
                    'category' => 'performance' // Solo categoria performance
                ]
            ]);
            
            error_log("[PAGESPEED] Esecuzione chiamata API per URL: {$this->url}");
            $success = $proxyService->execute();
            
            if ($success && !$proxyService->hasError()) {
                error_log("[PAGESPEED] API chiamata con successo");
                $this->pageSpeedData = $proxyService->getResult();
                
                // Salva i dati in cache per 24 ore
                $this->cache->set($cacheKey, $this->pageSpeedData, 86400);
                
                $this->processPageSpeedData();
            } else {
                error_log("[PAGESPEED ERROR] " . $proxyService->getErrorMessage());
                $this->results['pageSpeed']['error'] = $proxyService->getErrorMessage();
            }
        } catch (Exception $e) {
            // In caso di errore, continua senza dati PageSpeed
            $errorMsg = "Errore durante l'analisi PageSpeed: " . $e->getMessage();
            error_log("[PAGESPEED ERROR] " . $errorMsg);
            $this->results['pageSpeed']['error'] = $errorMsg;
        }
    }
    
    /**
     * Elabora i dati ottenuti da PageSpeed API
     */
    private function processPageSpeedData() {
        if (empty($this->pageSpeedData)) {
            return;
        }
        
        $this->results['pageSpeed']['dataAvailable'] = true;
        
        // Estrai il punteggio principale
        if (isset($this->pageSpeedData['lighthouseResult']['categories']['performance']['score'])) {
            $psScore = $this->pageSpeedData['lighthouseResult']['categories']['performance']['score'] * 100;
            $this->results['pageSpeed']['score'] = round($psScore);
        }
        
        // Estrai le metriche principali
        if (isset($this->pageSpeedData['lighthouseResult']['audits'])) {
            $audits = $this->pageSpeedData['lighthouseResult']['audits'];
            
            // Core Web Vitals
            $this->webVitals = [
                'LCP' => $this->extractMetric($audits, 'largest-contentful-paint'),
                'FID' => $this->extractMetric($audits, 'max-potential-fid'),
                'CLS' => $this->extractMetric($audits, 'cumulative-layout-shift'),
                'FCP' => $this->extractMetric($audits, 'first-contentful-paint'),
                'TBT' => $this->extractMetric($audits, 'total-blocking-time'),
                'TTI' => $this->extractMetric($audits, 'interactive')
            ];
            
            $this->results['pageSpeed']['webVitals'] = $this->webVitals;
            
            // Estrai opportunità di miglioramento
            $this->extractPageSpeedOpportunities($audits);
            
            // Estrai diagnostici
            $this->extractPageSpeedDiagnostics($audits);
        }
    }
    
    /**
     * Estrae una metrica specifica dai risultati di PageSpeed
     * 
     * @param array $audits Array di audit
     * @param string $id Identificativo della metrica
     * @return array Dati della metrica
     */
    private function extractMetric($audits, $id) {
        if (isset($audits[$id])) {
            $metric = $audits[$id];
            return [
                'score' => $metric['score'] ?? null,
                'value' => $metric['numericValue'] ?? null,
                'displayValue' => $metric['displayValue'] ?? null,
                'description' => $metric['description'] ?? null
            ];
        }
        return null;
    }
    
    /**
     * Estrae le opportunità di miglioramento dai risultati di PageSpeed
     * 
     * @param array $audits Array di audit
     */
    private function extractPageSpeedOpportunities($audits) {
        $opportunities = [];
        $opportunityIds = [
            'render-blocking-resources',
            'unused-css-rules',
            'unused-javascript',
            'offscreen-images',
            'unminified-css',
            'unminified-javascript',
            'uses-optimized-images',
            'uses-webp-images',
            'uses-responsive-images',
            'efficient-animated-content',
            'uses-rel-preconnect',
            'uses-rel-preload',
            'server-response-time',
            'redirects',
            'uses-text-compression'
        ];
        
        foreach ($opportunityIds as $id) {
            if (isset($audits[$id]) && $audits[$id]['score'] < 1) {
                $opportunities[] = [
                    'id' => $id,
                    'title' => $audits[$id]['title'] ?? '',
                    'description' => $audits[$id]['description'] ?? '',
                    'score' => $audits[$id]['score'] ?? 0,
                    'displayValue' => $audits[$id]['displayValue'] ?? '',
                    'details' => $this->simplifyAuditDetails($audits[$id]['details'] ?? [])
                ];
            }
        }
        
        $this->results['pageSpeed']['opportunities'] = $opportunities;
    }
    
    /**
     * Estrae i diagnostici dai risultati di PageSpeed
     * 
     * @param array $audits Array di audit
     */
    private function extractPageSpeedDiagnostics($audits) {
        $diagnostics = [];
        $diagnosticIds = [
            'total-byte-weight',
            'dom-size',
            'critical-request-chains',
            'network-requests',
            'main-thread-tasks',
            'bootup-time',
            'third-party-summary',
            'third-party-facades',
            'legacy-javascript',
            'long-tasks'
        ];
        
        foreach ($diagnosticIds as $id) {
            if (isset($audits[$id])) {
                $diagnostics[] = [
                    'id' => $id,
                    'title' => $audits[$id]['title'] ?? '',
                    'description' => $audits[$id]['description'] ?? '',
                    'score' => $audits[$id]['score'] ?? null,
                    'displayValue' => $audits[$id]['displayValue'] ?? '',
                    'numericValue' => $audits[$id]['numericValue'] ?? null
                ];
            }
        }
        
        $this->results['pageSpeed']['diagnostics'] = $diagnostics;
    }
    
    /**
     * Semplifica i dettagli degli audit per ridurre la dimensione dei dati
     * 
     * @param array $details Dettagli dell'audit
     * @return array Dettagli semplificati
     */
    private function simplifyAuditDetails($details) {
        $simplified = [];
        
        // Include solo elementi essenziali
        if (isset($details['type'])) {
            $simplified['type'] = $details['type'];
        }
        
        if (isset($details['items']) && is_array($details['items'])) {
            // Limita il numero di elementi a 5 per risparmiare spazio
            $simplified['items'] = array_slice($details['items'], 0, 5);
        }
        
        return $simplified;
    }
    
    /**
     * Analizza i resource hints (preload, preconnect, prefetch, dns-prefetch)
     */
    private function analyzeResourceHints() {
        // Inizializza i risultati
        $this->results['resourceHints'] = [
            'preload' => 0,
            'preconnect' => 0,
            'prefetch' => 0,
            'dnsPrefetch' => 0,
            'total' => 0,
            'score' => 0
        ];
        
        // Cerca link con rel preload, preconnect, prefetch, dns-prefetch
        $links = $this->dom->getElementsByTagName('link');
        
        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            
            if ($rel === 'preload') {
                $this->results['resourceHints']['preload']++;
            }
            if ($rel === 'preconnect') {
                $this->results['resourceHints']['preconnect']++;
            }
            if ($rel === 'prefetch') {
                $this->results['resourceHints']['prefetch']++;
            }
            if ($rel === 'dns-prefetch') {
                $this->results['resourceHints']['dnsPrefetch']++;
            }
        }
        
        // Calcola il totale
        $this->results['resourceHints']['total'] = 
            $this->results['resourceHints']['preload'] +
            $this->results['resourceHints']['preconnect'] +
            $this->results['resourceHints']['prefetch'] +
            $this->results['resourceHints']['dnsPrefetch'];
        
        // Valuta l'utilizzo dei resource hints
        $score = 0;
        
        // Valuta preconnect/dns-prefetch (più critici per la performance iniziale)
        if ($this->results['resourceHints']['preconnect'] > 0 || 
            $this->results['resourceHints']['dnsPrefetch'] > 0) {
            $score += 40;
        }
        
        // Valuta preload (utile per risorse critiche)
        if ($this->results['resourceHints']['preload'] > 0) {
            $score += 30;
        }
        
        // Valuta prefetch (utile per migliorare la navigazione)
        if ($this->results['resourceHints']['prefetch'] > 0) {
            $score += 20;
        }
        
        // Bonus per uso estensivo ma non eccessivo
        $total = $this->results['resourceHints']['total'];
        if ($total >= 3 && $total <= 10) {
            $score += 10;
        } else if ($total > 10) {
            $score -= 10; // Penalità per uso eccessivo (può avere effetto opposto)
        }
        
        $this->results['resourceHints']['score'] = min(100, max(0, $score));
    }
    
    /**
     * Analizza gli elementi che bloccano il rendering
     */
    private function analyzeRenderBlocking() {
        // Inizializza i risultati
        $this->results['renderBlocking'] = [
            'scripts' => 0,
            'styles' => 0,
            'total' => 0,
            'score' => 100 // Punteggio iniziale (più alto è meglio)
        ];
        
        // Conta gli script che bloccano il rendering (senza async o defer)
        $scripts = $this->dom->getElementsByTagName('script');
        $blockingScripts = 0;
        
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            $async = $script->hasAttribute('async');
            $defer = $script->hasAttribute('defer');
            $type = $script->getAttribute('type');
            
            if (!empty($src) && !$async && !$defer && $type !== 'module') {
                $blockingScripts++;
            }
        }
        
        // Conta i CSS che bloccano il rendering (non inline critical CSS)
        $links = $this->dom->getElementsByTagName('link');
        $blockingStyles = 0;
        
        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            $media = $link->getAttribute('media');
            
            if ($rel === 'stylesheet' && (!$media || $media === 'all' || strpos($media, 'screen') !== false)) {
                $blockingStyles++;
            }
        }
        
        $this->results['renderBlocking']['scripts'] = $blockingScripts;
        $this->results['renderBlocking']['styles'] = $blockingStyles;
        $this->results['renderBlocking']['total'] = $blockingScripts + $blockingStyles;
        
        // Valuta l'impatto degli elementi bloccanti
        $total = $this->results['renderBlocking']['total'];
        
        // Penalizza in base al numero di risorse bloccanti
        if ($total === 0) {
            // Perfetto, nessun blocco
        } else if ($total <= 2) {
            $this->results['renderBlocking']['score'] -= 20;
        } else if ($total <= 5) {
            $this->results['renderBlocking']['score'] -= 40;
        } else if ($total <= 8) {
            $this->results['renderBlocking']['score'] -= 60;
        } else {
            $this->results['renderBlocking']['score'] -= 80;
        }
        
        // Assicura che il punteggio sia tra 0 e 100
        $this->results['renderBlocking']['score'] = max(0, $this->results['renderBlocking']['score']);
    }
    
    /**
     * Analizza le ottimizzazioni delle immagini
     */
    private function analyzeImageOptimization() {
        // Inizializza i risultati
        $this->results['imageOptimization'] = [
            'total' => 0,
            'withLazyLoading' => 0,
            'withSrcset' => 0,
            'withSize' => 0,
            'withAlt' => 0,
            'score' => 0
        ];
        
        // Analizza tutte le immagini
        $images = $this->dom->getElementsByTagName('img');
        $this->results['imageOptimization']['total'] = $images->length;
        
        foreach ($images as $img) {
            // Controllo lazy loading
            if ($img->hasAttribute('loading') && $img->getAttribute('loading') === 'lazy') {
                $this->results['imageOptimization']['withLazyLoading']++;
            }
            
            // Controllo srcset per immagini responsive
            if ($img->hasAttribute('srcset')) {
                $this->results['imageOptimization']['withSrcset']++;
            }
            
            // Controllo dimensioni specificate
            if ($img->hasAttribute('width') && $img->hasAttribute('height')) {
                $this->results['imageOptimization']['withSize']++;
            }
            
            // Controllo attributo alt
            if ($img->hasAttribute('alt')) {
                $this->results['imageOptimization']['withAlt']++;
            }
        }
        
        // Calcola le percentuali per valutazione
        if ($this->results['imageOptimization']['total'] > 0) {
            $lazyPercentage = ($this->results['imageOptimization']['withLazyLoading'] / $this->results['imageOptimization']['total']) * 100;
            $srcsetPercentage = ($this->results['imageOptimization']['withSrcset'] / $this->results['imageOptimization']['total']) * 100;
            $sizePercentage = ($this->results['imageOptimization']['withSize'] / $this->results['imageOptimization']['total']) * 100;
            $altPercentage = ($this->results['imageOptimization']['withAlt'] / $this->results['imageOptimization']['total']) * 100;
            
            // Calcola il punteggio
            $score = ($lazyPercentage * 0.3) + ($srcsetPercentage * 0.3) + ($sizePercentage * 0.2) + ($altPercentage * 0.2);
            $this->results['imageOptimization']['score'] = round($score);
        } else {
            // Se non ci sono immagini, assegna un punteggio neutro
            $this->results['imageOptimization']['score'] = 50;
        }
    }
    
    /**
     * Analizza le ottimizzazioni del caching
     */
    private function analyzeCaching() {
        // Inizializza i risultati
        $this->results['caching'] = [
            'hasCacheControl' => false,
            'hasExpires' => false,
            'hasETag' => false,
            'hasLastModified' => false,
            'maxAge' => 0,
            'score' => 0
        ];
        
        // Verifica la presenza di header di caching
        if (isset($this->headers['Cache-Control'])) {
            $this->results['caching']['hasCacheControl'] = true;
            
            // Estrai max-age se presente
            if (preg_match('/max-age=([0-9]+)/', $this->headers['Cache-Control'], $matches)) {
                $this->results['caching']['maxAge'] = intval($matches[1]);
            }
        }
        
        if (isset($this->headers['Expires'])) {
            $this->results['caching']['hasExpires'] = true;
        }
        
        if (isset($this->headers['ETag'])) {
            $this->results['caching']['hasETag'] = true;
        }
        
        if (isset($this->headers['Last-Modified'])) {
            $this->results['caching']['hasLastModified'] = true;
        }
        
        // Valuta la qualità delle impostazioni di cache
        $score = 0;
        
        // Valuta Cache-Control e max-age
        if ($this->results['caching']['hasCacheControl']) {
            $score += 30;
            
            // Valuta max-age
            $maxAge = $this->results['caching']['maxAge'];
            if ($maxAge >= 2592000) { // 30 giorni
                $score += 30;
            } else if ($maxAge >= 604800) { // 7 giorni
                $score += 25;
            } else if ($maxAge >= 86400) { // 1 giorno
                $score += 20;
            } else if ($maxAge >= 3600) { // 1 ora
                $score += 10;
            } else {
                $score += 5;
            }
        }
        
        // Valuta altri header
        if ($this->results['caching']['hasExpires']) {
            $score += 10;
        }
        
        if ($this->results['caching']['hasETag']) {
            $score += 15;
        }
        
        if ($this->results['caching']['hasLastModified']) {
            $score += 15;
        }
        
        $this->results['caching']['score'] = min(100, $score);
    }
    
    /**
     * Calcola i punteggi finali
     */
    private function calculateScores() {
        // Pesi per le diverse categorie di performance
        $weights = [
            'pageSpeed' => 0.40, // Se disponibile
            'localMetrics' => 0.30,
            'renderBlocking' => 0.10,
            'imageOptimization' => 0.10,
            'resourceHints' => 0.05,
            'caching' => 0.05
        ];
        
        // Se PageSpeed non è disponibile, redistribuisce i pesi
        if (!$this->results['pageSpeed']['dataAvailable']) {
            $weights['localMetrics'] = 0.50;
            $weights['renderBlocking'] = 0.20;
            $weights['imageOptimization'] = 0.15;
            $weights['resourceHints'] = 0.05;
            $weights['caching'] = 0.10;
        }
        
        // Calcola il punteggio totale
        $totalScore = 0;
        $appliedWeightSum = 0;
        
        foreach ($weights as $category => $weight) {
            if (isset($this->results[$category]['score'])) {
                // Se PageSpeed non è disponibile, salta quella categoria
                if ($category === 'pageSpeed' && !$this->results['pageSpeed']['dataAvailable']) {
                    continue;
                }
                
                $totalScore += $this->results[$category]['score'] * $weight;
                $appliedWeightSum += $weight;
            }
        }
        
        // Normalizza il punteggio in base ai pesi effettivamente applicati
        if ($appliedWeightSum > 0) {
            $this->results['totalScore'] = round($totalScore / $appliedWeightSum, 2);
        } else {
            $this->results['totalScore'] = 0;
        }
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return bool True se il fallback ha avuto successo
     */
    protected function implementFallback() {
        // In caso di errore, utilizzare solo le metriche locali se disponibili
        if (isset($this->results['localMetrics']) && !empty($this->results['localMetrics'])) {
            // Usa le metriche locali come punteggio principale
            $this->results['totalScore'] = $this->results['localMetrics']['score'];
            $this->results['fallbackUsed'] = true;
            return true;
        }
        
        // Se non c'è nessun dato, crea un risultato base
        $this->results = [
            'localMetrics' => ['score' => 0],
            'pageSpeed' => ['score' => 0, 'dataAvailable' => false],
            'renderBlocking' => ['score' => 0],
            'imageOptimization' => ['score' => 0],
            'resourceHints' => ['score' => 0],
            'caching' => ['score' => 0],
            'totalScore' => 0,
            'error' => $this->errorMessage,
            'fallbackUsed' => true
        ];
        
        return true;
    }
}