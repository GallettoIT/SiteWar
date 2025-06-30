<?php
/**
 * TechnologyAnalyzer
 * 
 * Analizzatore specializzato per l'identificazione delle tecnologie utilizzate
 * da un sito web. Implementa un'alternativa a Wappalyzer utilizzando pattern
 * di riconoscimento per framework, CMS, linguaggi, librerie e altre tecnologie.
 * 
 * Pattern implementati:
 * - Strategy
 * - Template Method
 * - Composite (per i pattern di rilevamento)
 */

require_once __DIR__ . '/BaseAnalyzer.php';
require_once __DIR__ . '/../../core/ServiceFactory.php';
require_once __DIR__ . '/../../utils/Cache.php';

class TechnologyAnalyzer extends BaseAnalyzer {
    /**
     * @var ServiceFactory Factory per servizi
     */
    private $serviceFactory;
    
    /**
     * @var Cache Sistema di cache
     */
    private $cache;
    
    /**
     * @var array Database delle tecnologie con pattern di rilevamento
     */
    private $techDatabase;
    
    /**
     * @var array Tecnologie rilevate
     */
    private $detectedTechnologies;
    
    /**
     * @var array Mappe di implementazione comuni
     */
    private $implementationMaps;
    
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
        $this->initTechDatabase();
        $this->initImplementationMaps();
    }
    
    /**
     * Inizializza il database delle tecnologie con pattern di rilevamento
     */
    private function initTechDatabase() {
        // Questo è un database semplificato delle tecnologie con pattern di rilevamento
        // In un ambiente reale, questo potrebbe essere caricato da un file JSON o un database
        $this->techDatabase = [
            // CMS
            'WordPress' => [
                'html' => ['wp-content', 'wp-includes'],
                'meta' => ['generator' => 'WordPress'],
                'headers' => ['X-Powered-By' => 'WordPress'],
                'script' => ['wp-embed.min.js', 'wp-emoji', '/wp-content/'],
                'cookie' => ['wordpress_', 'wp-settings-']
            ],
            'Drupal' => [
                'html' => ['Drupal.settings', 'drupal.org'],
                'meta' => ['generator' => 'Drupal'],
                'headers' => ['X-Generator' => 'Drupal'],
                'script' => ['/misc/drupal.js', 'drupal.js'],
                'cookie' => ['Drupal.visitor']
            ],
            'Joomla' => [
                'html' => ['/components/com_', '/modules/mod_'],
                'meta' => ['generator' => 'Joomla'],
                'headers' => ['X-Content-Encoded-By' => 'Joomla'],
                'script' => ['media/jui/js/jquery.min.js', 'media/system/js/core.js']
            ],
            'Magento' => [
                'html' => ['Mage.', 'skin/frontend/'],
                'cookie' => ['frontend=', 'mage-cache-']
            ],
            'Shopify' => [
                'html' => ['Shopify.', 'cdn.shopify.com'],
                'headers' => ['X-Shopify-Stage']
            ],
            'Wix' => [
                'html' => ['wix.com', '_wixCIDX'],
                'meta' => ['generator' => 'Wix.com']
            ],
            'Squarespace' => [
                'html' => ['static.squarespace.com'],
                'meta' => ['generator' => 'Squarespace']
            ],
            
            // Framework JavaScript
            'React' => [
                'html' => ['_reactRootContainer', '__REACT_DEVTOOLS_GLOBAL_HOOK__'],
                'script' => ['react.js', 'react.min.js', 'react.production.min.js']
            ],
            'Vue.js' => [
                'html' => ['__vue__', 'data-v-'],
                'script' => ['vue.js', 'vue.min.js']
            ],
            'Angular' => [
                'html' => ['ng-app', 'ng-controller', 'ng-model', '_ng'],
                'script' => ['angular.js', 'angular.min.js']
            ],
            'jQuery' => [
                'html' => ['jQuery(', '$.'],
                'script' => ['jquery.js', 'jquery.min.js']
            ],
            'Bootstrap' => [
                'html' => ['class="container"', 'class="row"', 'class="col-'],
                'script' => ['bootstrap.js', 'bootstrap.min.js', 'bootstrap.bundle.min.js'],
                'css' => ['bootstrap.css', 'bootstrap.min.css']
            ],
            
            // Framework PHP/Backend
            'Laravel' => [
                'headers' => ['X-Powered-By' => 'Laravel'],
                'cookie' => ['laravel_session']
            ],
            'Symfony' => [
                'cookie' => ['SYMFONY_PHPSESSID'],
                'headers' => ['X-Symfony-']
            ],
            'CodeIgniter' => [
                'cookie' => ['ci_session']
            ],
            'CakePHP' => [
                'headers' => ['X-Powered-By' => 'CakePHP'],
                'cookie' => ['CAKEPHP']
            ],
            
            // Server
            'Apache' => [
                'headers' => ['Server' => 'Apache']
            ],
            'Nginx' => [
                'headers' => ['Server' => 'nginx']
            ],
            'IIS' => [
                'headers' => ['Server' => 'IIS']
            ],
            'LiteSpeed' => [
                'headers' => ['Server' => 'LiteSpeed']
            ],
            
            // Linguaggi
            'PHP' => [
                'headers' => ['X-Powered-By' => 'PHP']
            ],
            'ASP.NET' => [
                'headers' => ['X-Powered-By' => 'ASP.NET'],
                'cookie' => ['ASP.NET_SessionId']
            ],
            'Ruby on Rails' => [
                'headers' => ['X-Powered-By' => 'Ruby on Rails'],
                'cookie' => ['_rails_']
            ],
            'Python' => [
                'headers' => ['X-Powered-By' => 'Django']
            ],
            
            // Analytics e Marketing
            'Google Analytics' => [
                'html' => ['google-analytics.com', 'GoogleAnalyticsObject', 'ga(', 'gtag('],
                'script' => ['analytics.js', 'ga.js', 'gtag/js']
            ],
            'Google Tag Manager' => [
                'html' => ['googletagmanager.com', 'gtm.js'],
                'script' => ['gtm.js']
            ],
            'Facebook Pixel' => [
                'html' => ['connect.facebook.net/en_US/fbevents.js', 'fbq('],
                'script' => ['fbevents.js']
            ],
            'Hotjar' => [
                'html' => ['hotjar.com', 'hjSettings'],
                'script' => ['hotjar.js', 'static.hotjar.com']
            ],
            
            // CDN
            'Cloudflare' => [
                'headers' => ['Server' => 'cloudflare', 'CF-RAY', 'CF-Cache-Status']
            ],
            'Akamai' => [
                'headers' => ['X-Akamai-', 'Server' => 'AkamaiGHost']
            ],
            'Fastly' => [
                'headers' => ['Fastly-']
            ],
            'CloudFront' => [
                'headers' => ['X-Amz-Cf-', 'Via' => 'CloudFront']
            ],
            
            // Cache
            'Varnish' => [
                'headers' => ['X-Varnish', 'Via' => 'varnish']
            ],
            'Redis' => [
                'headers' => ['X-Powered-By' => 'Redis']
            ],
            'Memcached' => [
                'headers' => ['X-Powered-By' => 'Memcached']
            ],
            
            // Sicurezza
            'reCAPTCHA' => [
                'html' => ['recaptcha', 'google.com/recaptcha'],
                'script' => ['recaptcha']
            ],
            'hCaptcha' => [
                'html' => ['hcaptcha.com', 'h-captcha'],
                'script' => ['hcaptcha']
            ],
            
            // Funzionalità
            'Font Awesome' => [
                'html' => ['fa-', 'fontawesome'],
                'script' => ['fontawesome'],
                'css' => ['font-awesome']
            ],
            'Google Fonts' => [
                'html' => ['fonts.googleapis.com'],
                'css' => ['fonts.googleapis.com']
            ],
            'jQuery UI' => [
                'html' => ['jquery-ui'],
                'script' => ['jquery-ui.js', 'jquery-ui.min.js']
            ],
            'Moment.js' => [
                'script' => ['moment.js', 'moment.min.js']
            ],
            'Lodash' => [
                'script' => ['lodash.js', 'lodash.min.js', '_']
            ],
            'Popper.js' => [
                'script' => ['popper.js', 'popper.min.js']
            ]
        ];
    }
    
    /**
     * Inizializza le mappe di implementazione comuni
     */
    private function initImplementationMaps() {
        // Mappe per definire relazioni tra tecnologie e best practice comuni
        $this->implementationMaps = [
            // Framework frontend e best practice correlate
            'React' => [
                'recommended' => ['React Router', 'Redux', 'Webpack', 'Babel', 'ESLint'],
                'performance' => ['Code Splitting', 'React.memo', 'useCallback', 'useMemo'],
                'security' => ['DOMPurify', 'helmet']
            ],
            'Vue.js' => [
                'recommended' => ['Vue Router', 'Vuex', 'Nuxt.js', 'Vite'],
                'performance' => ['Lazy Loading', 'Keep-Alive', 'Virtual DOM'],
                'security' => ['Vue Security']
            ],
            'Angular' => [
                'recommended' => ['RxJS', 'NgRx', 'Angular Material'],
                'performance' => ['Lazy Loading', 'AOT Compilation', 'OnPush Change Detection'],
                'security' => ['Angular Sanitization']
            ],
            
            // CMS e best practice correlate
            'WordPress' => [
                'recommended' => ['Caching Plugin', 'SEO Plugin', 'Security Plugin'],
                'performance' => ['Image Optimization', 'Database Optimization', 'Minification'],
                'security' => ['WP Security Updates', 'Strong Authentication', 'Firewall Plugin']
            ],
            'Drupal' => [
                'recommended' => ['Drupal Views', 'Pathauto', 'Caching'],
                'performance' => ['BigPipe', 'Lazy Loading', 'Views Optimization'],
                'security' => ['Security Kit', 'Drupal Security Updates']
            ]
        ];
    }
    
    /**
     * Esegue l'analisi delle tecnologie specifica
     */
    protected function doAnalyze() {
        // Inizializza le tecnologie rilevate
        $this->detectedTechnologies = [];
        
        // Analizza i meta tag
        $this->analyzeMetaTags();
        
        // Analizza gli script
        $this->analyzeScripts();
        
        // Analizza CSS
        $this->analyzeCSS();
        
        // Analizza HTML
        $this->analyzeHTML();
        
        // Analizza cookie
        $this->analyzeCookies();
        
        // Analizza header HTTP
        $this->analyzeHeaders();
        
        // Analizza indicatori JavaScript
        $this->analyzeJavaScript();
        
        // Integra dati da API esterne se possibile
        $this->integrateExternalData();
        
        // Analizza relazioni tra tecnologie
        $this->analyzeRelationships();
        
        // Calcola i punteggi
        $this->calculateScores();
    }
    
    /**
     * Analizza i meta tag per identificare le tecnologie
     */
    private function analyzeMetaTags() {
        $metaTags = $this->dom->getElementsByTagName('meta');
        
        foreach ($metaTags as $meta) {
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');
            
            if ($name === 'generator' && !empty($content)) {
                foreach ($this->techDatabase as $tech => $patterns) {
                    if (isset($patterns['meta']['generator'])) {
                        $pattern = $patterns['meta']['generator'];
                        if (stripos($content, $pattern) !== false) {
                            $this->addDetectedTechnology($tech, 'meta', 90);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Analizza gli script per identificare le tecnologie
     */
    private function analyzeScripts() {
        $scripts = $this->dom->getElementsByTagName('script');
        
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            
            if (!empty($src)) {
                foreach ($this->techDatabase as $tech => $patterns) {
                    if (isset($patterns['script'])) {
                        foreach ($patterns['script'] as $pattern) {
                            if (stripos($src, $pattern) !== false) {
                                $this->addDetectedTechnology($tech, 'script', 85);
                            }
                        }
                    }
                }
            }
            
            // Analizza anche il contenuto degli script inline
            $content = $script->textContent;
            if (!empty($content)) {
                foreach ($this->techDatabase as $tech => $patterns) {
                    if (isset($patterns['html'])) {
                        foreach ($patterns['html'] as $pattern) {
                            if (stripos($content, $pattern) !== false) {
                                $this->addDetectedTechnology($tech, 'script_inline', 75);
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Analizza i fogli di stile per identificare le tecnologie
     */
    private function analyzeCSS() {
        $links = $this->dom->getElementsByTagName('link');
        
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'stylesheet') {
                $href = $link->getAttribute('href');
                
                if (!empty($href)) {
                    foreach ($this->techDatabase as $tech => $patterns) {
                        if (isset($patterns['css'])) {
                            foreach ($patterns['css'] as $pattern) {
                                if (stripos($href, $pattern) !== false) {
                                    $this->addDetectedTechnology($tech, 'css', 80);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Analizza anche i tag style inline
        $styles = $this->dom->getElementsByTagName('style');
        foreach ($styles as $style) {
            $content = $style->textContent;
            
            foreach ($this->techDatabase as $tech => $patterns) {
                if (isset($patterns['html'])) {
                    foreach ($patterns['html'] as $pattern) {
                        if (stripos($content, $pattern) !== false) {
                            $this->addDetectedTechnology($tech, 'style_inline', 70);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Analizza il contenuto HTML per identificare le tecnologie
     */
    private function analyzeHTML() {
        $html = $this->pageContent;
        
        foreach ($this->techDatabase as $tech => $patterns) {
            if (isset($patterns['html'])) {
                foreach ($patterns['html'] as $pattern) {
                    if (stripos($html, $pattern) !== false) {
                        $this->addDetectedTechnology($tech, 'html', 75);
                    }
                }
            }
        }
        
        // Analisi delle classi e ID frequenti
        $this->analyzeCommonClassesAndIds();
    }
    
    /**
     * Analizza classi e ID HTML comuni per identificare framework e librerie
     */
    private function analyzeCommonClassesAndIds() {
        $classPatterns = [
            'Bootstrap' => ['container', 'row', 'col-', 'btn-', 'card', 'navbar', 'modal'],
            'Tailwind CSS' => ['text-', 'bg-', 'flex', 'p-', 'm-', 'w-', 'h-', 'rounded-'],
            'Bulma' => ['column', 'buttons', 'notification', 'hero', 'section', 'tile'],
            'Foundation' => ['grid-x', 'cell', 'callout', 'button', 'top-bar', 'orbit'],
            'Materialize CSS' => ['container', 'row', 'col s', 'btn', 'card', 'navbar', 'collection']
        ];
        
        // Estrae tutte le classi dal DOM
        $allNodes = $this->dom->getElementsByTagName('*');
        $classesFound = [];
        
        foreach ($allNodes as $node) {
            if ($node->hasAttribute('class')) {
                $classes = explode(' ', $node->getAttribute('class'));
                foreach ($classes as $class) {
                    $class = trim($class);
                    if (!empty($class)) {
                        if (!isset($classesFound[$class])) {
                            $classesFound[$class] = 0;
                        }
                        $classesFound[$class]++;
                    }
                }
            }
        }
        
        // Verifica pattern di classi comuni
        foreach ($classPatterns as $tech => $patterns) {
            $matchCount = 0;
            $uniquePatterns = count($patterns);
            
            foreach ($patterns as $pattern) {
                foreach ($classesFound as $class => $count) {
                    if (stripos($class, $pattern) === 0) {
                        $matchCount++;
                        break;
                    }
                }
            }
            
            // Se più del 50% dei pattern è presente, considera la tecnologia rilevata
            if ($matchCount > 0 && ($matchCount / $uniquePatterns) > 0.5) {
                $this->addDetectedTechnology($tech, 'css_classes', 70);
            }
        }
    }
    
    /**
     * Analizza i cookie per identificare le tecnologie
     */
    private function analyzeCookies() {
        if (isset($this->headers['Set-Cookie'])) {
            $cookies = is_array($this->headers['Set-Cookie']) ? 
                       $this->headers['Set-Cookie'] : 
                       [$this->headers['Set-Cookie']];
            
            foreach ($cookies as $cookie) {
                foreach ($this->techDatabase as $tech => $patterns) {
                    if (isset($patterns['cookie'])) {
                        foreach ($patterns['cookie'] as $pattern) {
                            if (stripos($cookie, $pattern) !== false) {
                                $this->addDetectedTechnology($tech, 'cookie', 85);
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Analizza gli header HTTP per identificare le tecnologie
     */
    private function analyzeHeaders() {
        foreach ($this->headers as $header => $value) {
            foreach ($this->techDatabase as $tech => $patterns) {
                if (isset($patterns['headers'][$header])) {
                    $pattern = $patterns['headers'][$header];
                    if (is_string($value)) {
                        if (stripos($value, $pattern) !== false) {
                            $this->addDetectedTechnology($tech, 'header', 90);
                        }
                    } elseif (is_array($value)) {
                        foreach ($value as $val) {
                            if (stripos($val, $pattern) !== false) {
                                $this->addDetectedTechnology($tech, 'header', 90);
                            }
                        }
                    }
                }
                
                // Controllo anche header generici (es. 'CF-' per Cloudflare)
                if (isset($patterns['headers'])) {
                    foreach ($patterns['headers'] as $headerPattern => $valuePattern) {
                        if ($headerPattern !== $header && stripos($header, $headerPattern) !== false) {
                            $this->addDetectedTechnology($tech, 'header', 90);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Analizza gli indicatori JavaScript per identificare librerie e framework
     */
    private function analyzeJavaScript() {
        $jsPatterns = [
            'jQuery' => ['jQuery', '$', 'jquery'],
            'React' => ['React', 'ReactDOM', '_reactRootContainer', '__REACT_DEVTOOLS_GLOBAL_HOOK__'],
            'Vue.js' => ['Vue', 'Vuex', '__vue__', 'Vue.component'],
            'Angular' => ['angular', 'ng', 'ngModel', 'ngApp'],
            'Lodash' => ['_', 'lodash', '_.map', '_.filter'],
            'Moment.js' => ['moment', 'moment.js'],
            'Underscore.js' => ['_', '_.map', '_.each'],
            'Google Tag Manager' => ['dataLayer', 'gtm_'],
            'Google Analytics' => ['ga', 'analytics', 'gtag'],
            'Facebook Pixel' => ['fbq', 'fb-pixel']
        ];
        
        // Cerca variabili globali nel contenuto JavaScript
        $scripts = $this->dom->getElementsByTagName('script');
        $jsContent = '';
        
        foreach ($scripts as $script) {
            if (!$script->hasAttribute('src')) {
                $jsContent .= $script->textContent . ' ';
            }
        }
        
        // Cerca pattern di JS
        foreach ($jsPatterns as $tech => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match('/\b' . preg_quote($pattern, '/') . '\b/', $jsContent)) {
                    $this->addDetectedTechnology($tech, 'js_pattern', 75);
                }
            }
        }
    }
    
    /**
     * Integra dati da API esterne quando possibile
     * 
     * Nota: Questo metodo è attualmente commentato perché "technology" non è
     * configurato nel file services.php. In futuro, potresti aggiungere questa
     * configurazione per utilizzare servizi come Wappalyzer API.
     */
    private function integrateExternalData() {
        // Il servizio 'technology' non è attualmente configurato in services.php
        // Questo metodo può essere riesaminato in futuro se si aggiunge l'integrazione
        // con un servizio di rilevamento tecnologie (es. Wappalyzer API)
        error_log("[TECHNOLOGY] Integrazione dati esterni: servizio non configurato");
        
        // Versione precedente commentata per riferimento:
        /*
        try {
            error_log("[TECHNOLOGY] Avvio integrazione dati esterni per URL: {$this->url}");
            
            // Utilizza il servizio di proxy per integrare dati da API esterne
            $proxyService = $this->serviceFactory->createService('proxy', [
                'service' => 'technology',
                'timeout' => 5,
                'params' => ['url' => $this->url]
            ]);
            
            $success = $proxyService->execute();
            
            if ($success && !$proxyService->hasError()) {
                $techData = $proxyService->getResult();
                
                if (is_array($techData) && isset($techData['technologies'])) {
                    error_log("[TECHNOLOGY] Tecnologie rilevate da API esterna: " . count($techData['technologies']));
                    foreach ($techData['technologies'] as $tech) {
                        if (isset($tech['name'])) {
                            $this->addDetectedTechnology($tech['name'], 'external_api', 95, $tech['version'] ?? null);
                        }
                    }
                }
            } else {
                error_log("[TECHNOLOGY ERROR] Errore chiamata API: " . $proxyService->getErrorMessage());
            }
        } catch (Exception $e) {
            // In caso di errore, continua senza dati esterni
            error_log("[TECHNOLOGY ERROR] Eccezione durante l'integrazione dati esterni: " . $e->getMessage());
        }
        */
    }
    
    /**
     * Analizza le relazioni tra le tecnologie rilevate
     */
    private function analyzeRelationships() {
        // Inizializza i risultati per le relazioni
        $this->results['relationships'] = [
            'frontend_backend' => 0,
            'security_features' => 0,
            'analytics_marketing' => 0,
            'performance_optimization' => 0,
            'technology_stack_score' => 0
        ];
        
        // Categorie di tecnologie
        $categories = [
            'frontend' => ['React', 'Vue.js', 'Angular', 'jQuery', 'Bootstrap', 'Tailwind CSS'],
            'backend' => ['PHP', 'ASP.NET', 'Ruby on Rails', 'Python', 'Laravel', 'Symfony', 'CodeIgniter', 'CakePHP'],
            'cms' => ['WordPress', 'Drupal', 'Joomla', 'Magento', 'Shopify', 'Wix', 'Squarespace'],
            'security' => ['reCAPTCHA', 'hCaptcha', 'Cloudflare', 'Akamai'],
            'analytics' => ['Google Analytics', 'Google Tag Manager', 'Hotjar', 'Facebook Pixel'],
            'performance' => ['Varnish', 'Redis', 'Memcached', 'Cloudflare', 'Akamai', 'Fastly', 'CloudFront']
        ];
        
        // Conta le tecnologie rilevate per categoria
        $categoryCounts = [];
        foreach ($categories as $category => $technologies) {
            $categoryCounts[$category] = 0;
            foreach ($technologies as $tech) {
                if (isset($this->detectedTechnologies[$tech])) {
                    $categoryCounts[$category]++;
                }
            }
        }
        
        // Valuta la complementarità frontend/backend
        if ($categoryCounts['frontend'] > 0 && ($categoryCounts['backend'] > 0 || $categoryCounts['cms'] > 0)) {
            $this->results['relationships']['frontend_backend'] = min(100, ($categoryCounts['frontend'] + $categoryCounts['backend'] + $categoryCounts['cms']) * 20);
        }
        
        // Valuta le funzionalità di sicurezza
        $this->results['relationships']['security_features'] = min(100, $categoryCounts['security'] * 25);
        
        // Valuta le funzionalità di analytics e marketing
        $this->results['relationships']['analytics_marketing'] = min(100, $categoryCounts['analytics'] * 25);
        
        // Valuta le ottimizzazioni di performance
        $this->results['relationships']['performance_optimization'] = min(100, $categoryCounts['performance'] * 20);
        
        // Punteggio complessivo dello stack tecnologico
        $this->results['relationships']['technology_stack_score'] = ($this->results['relationships']['frontend_backend'] * 0.4) +
                                                                    ($this->results['relationships']['security_features'] * 0.2) +
                                                                    ($this->results['relationships']['analytics_marketing'] * 0.2) +
                                                                    ($this->results['relationships']['performance_optimization'] * 0.2);
    }
    
    /**
     * Aggiunge una tecnologia rilevata all'elenco
     * 
     * @param string $technology Nome della tecnologia
     * @param string $source Fonte del rilevamento
     * @param int $confidence Livello di confidenza (0-100)
     * @param string|null $version Versione della tecnologia, se disponibile
     */
    private function addDetectedTechnology($technology, $source, $confidence, $version = null) {
        if (!isset($this->detectedTechnologies[$technology])) {
            $this->detectedTechnologies[$technology] = [
                'name' => $technology,
                'sources' => [],
                'confidence' => 0,
                'version' => $version
            ];
        }
        
        // Aggiunge la fonte se non è già presente
        if (!in_array($source, $this->detectedTechnologies[$technology]['sources'])) {
            $this->detectedTechnologies[$technology]['sources'][] = $source;
        }
        
        // Aggiorna il livello di confidenza (prende il più alto)
        $this->detectedTechnologies[$technology]['confidence'] = max(
            $this->detectedTechnologies[$technology]['confidence'], 
            $confidence
        );
        
        // Aggiorna la versione se non era disponibile
        if ($version !== null && $this->detectedTechnologies[$technology]['version'] === null) {
            $this->detectedTechnologies[$technology]['version'] = $version;
        }
    }
    
    /**
     * Calcola i punteggi finali
     */
    private function calculateScores() {
        // Categorizza le tecnologie rilevate
        $categories = [
            'cms' => ['WordPress', 'Drupal', 'Joomla', 'Magento', 'Shopify', 'Wix', 'Squarespace'],
            'javascript' => ['jQuery', 'React', 'Vue.js', 'Angular', 'Lodash', 'Moment.js', 'Popper.js'],
            'css' => ['Bootstrap', 'Tailwind CSS', 'Bulma', 'Foundation', 'Materialize CSS'],
            'server' => ['Apache', 'Nginx', 'IIS', 'LiteSpeed'],
            'backend' => ['PHP', 'ASP.NET', 'Ruby on Rails', 'Python', 'Laravel', 'Symfony', 'CodeIgniter', 'CakePHP'],
            'security' => ['reCAPTCHA', 'hCaptcha', 'Cloudflare', 'Akamai'],
            'analytics' => ['Google Analytics', 'Google Tag Manager', 'Hotjar', 'Facebook Pixel'],
            'performance' => ['Varnish', 'Redis', 'Memcached', 'Cloudflare', 'Akamai', 'Fastly', 'CloudFront'],
            'fonts' => ['Google Fonts', 'Font Awesome']
        ];
        
        // Inizializza i risultati
        $this->results['technologies'] = $this->detectedTechnologies;
        $this->results['categories'] = [];
        
        // Organizza le tecnologie per categorie
        foreach ($categories as $category => $technologies) {
            $this->results['categories'][$category] = [];
            
            foreach ($technologies as $tech) {
                if (isset($this->detectedTechnologies[$tech])) {
                    $this->results['categories'][$category][] = $tech;
                }
            }
        }
        
        // Calcola il punteggio di modernità
        $this->calculateModernityScore();
        
        // Calcola il punteggio di diversità
        $this->calculateDiversityScore();
        
        // Calcola il punteggio di robustezza
        $this->calculateRobustnessScore();
        
        // Calcola il punteggio di manutenibilità
        $this->calculateMaintainabilityScore();
        
        // Calcola il punteggio totale
        $this->calculateTotalScore();
        
        // Aggiungi consigli e best practice
        $this->addRecommendations();
    }
    
    /**
     * Calcola il punteggio di modernità delle tecnologie
     */
    private function calculateModernityScore() {
        // Tecnologie moderne con punteggi di modernità
        $modernityRatings = [
            // Modern JS Frameworks
            'React' => 95,
            'Vue.js' => 95,
            'Angular' => 90,
            'Svelte' => 98,
            
            // Modern CSS
            'Tailwind CSS' => 95,
            'CSS Grid' => 90,
            'CSS Flexbox' => 85,
            
            // Modern Backend
            'Laravel' => 90,
            'Symfony' => 85,
            'Ruby on Rails' => 80,
            'Django' => 85,
            'Express' => 85,
            'Flask' => 85,
            
            // HTTP/2 & HTTP/3 capable servers
            'Nginx' => 85,
            'Caddy' => 95,
            'LiteSpeed' => 90,
            
            // Modern CMS
            'Gatsby' => 95,
            'Next.js' => 95,
            'Nuxt.js' => 90,
            'Headless WordPress' => 85,
            'WordPress' => 75,
            'Drupal' => 80,
            
            // Legacy tech
            'jQuery' => 60,
            'Bootstrap 3' => 60,
            'PHP 5' => 50,
            'Apache' => 75,
            'Classic ASP' => 40
        ];
        
        $modernityScore = 0;
        $techCount = 0;
        
        foreach ($this->detectedTechnologies as $tech => $data) {
            if (isset($modernityRatings[$tech])) {
                $modernityScore += $modernityRatings[$tech];
                $techCount++;
            }
        }
        
        // Calcola il punteggio medio, con un minimo default
        $this->results['scores']['modernity'] = $techCount > 0 ? 
            round($modernityScore / $techCount) : 
            70; // Punteggio default
    }
    
    /**
     * Calcola il punteggio di diversità delle tecnologie
     */
    private function calculateDiversityScore() {
        // Conta quante categorie diverse sono coperte
        $coveredCategories = 0;
        
        foreach ($this->results['categories'] as $category => $techs) {
            if (count($techs) > 0) {
                $coveredCategories++;
            }
        }
        
        // Calcola il punteggio di diversità (più categorie = migliore, ma con limite)
        $totalCategories = count($this->results['categories']);
        $diversityRatio = $coveredCategories / $totalCategories;
        
        // Un buon bilanciamento è avere circa 60-80% delle categorie coperte
        if ($diversityRatio >= 0.6 && $diversityRatio <= 0.8) {
            $this->results['scores']['diversity'] = 100;
        } else if ($diversityRatio > 0.8) {
            // Troppa diversità può significare mancanza di focus
            $this->results['scores']['diversity'] = 90;
        } else {
            // Punteggio proporzionale alla copertura
            $this->results['scores']['diversity'] = round($diversityRatio * 100 * 1.5);
        }
        
        // Limite minimo
        $this->results['scores']['diversity'] = max(50, $this->results['scores']['diversity']);
    }
    
    /**
     * Calcola il punteggio di robustezza delle tecnologie
     */
    private function calculateRobustnessScore() {
        // Punteggi di robustezza per tecnologie specifiche
        $robustnessRatings = [
            // Strong, established technologies
            'Nginx' => 90,
            'Apache' => 85,
            'WordPress' => 80,
            'jQuery' => 85,
            'Bootstrap' => 85,
            'PHP' => 80,
            'MySQL' => 85,
            'PostgreSQL' => 90,
            'Redis' => 90,
            
            // Security and performance technologies
            'Cloudflare' => 95,
            'Akamai' => 95,
            'Varnish' => 90,
            'Memcached' => 85,
            'reCAPTCHA' => 90,
            
            // Modern frameworks with good reliability
            'React' => 85,
            'Vue.js' => 85,
            'Angular' => 80,
            'Laravel' => 85,
            'Ruby on Rails' => 80
        ];
        
        $robustnessScore = 0;
        $techCount = 0;
        
        foreach ($this->detectedTechnologies as $tech => $data) {
            if (isset($robustnessRatings[$tech])) {
                $robustnessScore += $robustnessRatings[$tech];
                $techCount++;
            }
        }
        
        // Bonus per tecnologie di sicurezza e performance
        $securityTechCount = count($this->results['categories']['security'] ?? []);
        $performanceTechCount = count($this->results['categories']['performance'] ?? []);
        
        $robustnessScore += ($securityTechCount * 5); // +5 per ogni tecnologia di sicurezza
        $robustnessScore += ($performanceTechCount * 5); // +5 per ogni tecnologia di performance
        
        // Calcola il punteggio medio
        $this->results['scores']['robustness'] = $techCount > 0 ? 
            min(100, round($robustnessScore / ($techCount + $securityTechCount + $performanceTechCount))) : 
            60; // Punteggio default
    }
    
    /**
     * Calcola il punteggio di manutenibilità delle tecnologie
     */
    private function calculateMaintainabilityScore() {
        // Punteggi di manutenibilità per tecnologie specifiche
        $maintainabilityRatings = [
            // High maintainability
            'React' => 90,
            'Vue.js' => 95,
            'Svelte' => 95,
            'Gatsby' => 90,
            'Next.js' => 90,
            'Nuxt.js' => 90,
            
            // Medium maintainability
            'Angular' => 75,
            'Laravel' => 85,
            'Django' => 85,
            'Express' => 80,
            'Ruby on Rails' => 80,
            
            // Lower maintainability (often due to legacy code or complexity)
            'jQuery' => 70,
            'WordPress' => 65,
            'Drupal' => 60,
            'Joomla' => 55,
            'Magento' => 50,
            'Classic ASP' => 40
        ];
        
        $maintainabilityScore = 0;
        $techCount = 0;
        
        foreach ($this->detectedTechnologies as $tech => $data) {
            if (isset($maintainabilityRatings[$tech])) {
                $maintainabilityScore += $maintainabilityRatings[$tech];
                $techCount++;
            }
        }
        
        // Penalità per troppe tecnologie (può complicare la manutenzione)
        $totalTechCount = count($this->detectedTechnologies);
        $penaltyFactor = 0;
        
        if ($totalTechCount > 15) {
            $penaltyFactor = ($totalTechCount - 15) * 2; // 2 punti per ogni tecnologia oltre 15
        }
        
        // Calcola il punteggio medio e applica la penalità
        $this->results['scores']['maintainability'] = $techCount > 0 ? 
            max(0, min(100, round($maintainabilityScore / $techCount) - $penaltyFactor)) : 
            65; // Punteggio default
    }
    
    /**
     * Calcola il punteggio totale
     */
    private function calculateTotalScore() {
        // Pesi per ciascuna categoria di punteggio
        $weights = [
            'modernity' => 0.3,
            'diversity' => 0.2,
            'robustness' => 0.3,
            'maintainability' => 0.2
        ];
        
        $totalScore = 0;
        
        foreach ($weights as $category => $weight) {
            if (isset($this->results['scores'][$category])) {
                $totalScore += $this->results['scores'][$category] * $weight;
            }
        }
        
        $this->results['totalScore'] = round($totalScore, 2);
        
        // Applica bonus/malus in base a pattern implementativi specifici
        $this->applyImplementationBonuses();
    }
    
    /**
     * Applica bonus o malus basati su pattern implementativi specifici
     */
    private function applyImplementationBonuses() {
        // Bonus per implementazioni ottimali
        $implementationBonus = 0;
        
        // Verifica la presenza di pattern implementativi migliori per le tecnologie rilevate
        foreach ($this->detectedTechnologies as $tech => $data) {
            if (isset($this->implementationMaps[$tech])) {
                // Per ora applica un bonus generico, in futuro potrebbe essere più specifico
                // basato su analisi dettagliata del codice
                $implementationBonus += 2;
            }
        }
        
        // Limita il bonus massimo
        $implementationBonus = min(10, $implementationBonus);
        
        // Applica il bonus
        $this->results['totalScore'] = min(100, $this->results['totalScore'] + $implementationBonus);
    }
    
    /**
     * Aggiunge consigli e best practice ai risultati
     */
    private function addRecommendations() {
        $this->results['recommendations'] = [
            'general' => [],
            'technology_specific' => []
        ];
        
        // Consigli generali basati sui punteggi
        if ($this->results['scores']['modernity'] < 70) {
            $this->results['recommendations']['general'][] = 'Considera l\'aggiornamento a framework e librerie più moderne';
        }
        
        if ($this->results['scores']['diversity'] < 60) {
            $this->results['recommendations']['general'][] = 'Diversifica lo stack tecnologico per coprire più aspetti (performance, sicurezza, analytics)';
        }
        
        if ($this->results['scores']['robustness'] < 70) {
            $this->results['recommendations']['general'][] = 'Aggiungi tecnologie per migliorare sicurezza e affidabilità';
        }
        
        if ($this->results['scores']['maintainability'] < 65) {
            $this->results['recommendations']['general'][] = 'Semplifica lo stack tecnologico per migliorare la manutenibilità';
        }
        
        // Consigli specifici per tecnologie rilevate
        foreach ($this->detectedTechnologies as $tech => $data) {
            if (isset($this->implementationMaps[$tech])) {
                $recommendations = [];
                
                // Aggiungi consigli basati sulle mappe di implementazione
                if (isset($this->implementationMaps[$tech]['recommended'])) {
                    $techSpecific = [
                        'technology' => $tech,
                        'recommendations' => $this->implementationMaps[$tech]['recommended'],
                        'performance_tips' => $this->implementationMaps[$tech]['performance'] ?? [],
                        'security_tips' => $this->implementationMaps[$tech]['security'] ?? []
                    ];
                    
                    $this->results['recommendations']['technology_specific'][] = $techSpecific;
                }
            }
        }
    }
    
    /**
     * Implementa una strategia di fallback in caso di errore
     * 
     * @return bool True se il fallback ha avuto successo
     */
    protected function implementFallback() {
        // Tenta di estrarre almeno le tecnologie base dagli header
        if (!empty($this->headers)) {
            $this->detectedTechnologies = [];
            
            // Controlla server
            if (isset($this->headers['Server'])) {
                $server = $this->headers['Server'];
                if (stripos($server, 'Apache') !== false) {
                    $this->addDetectedTechnology('Apache', 'header', 90);
                } else if (stripos($server, 'nginx') !== false) {
                    $this->addDetectedTechnology('Nginx', 'header', 90);
                } else if (stripos($server, 'IIS') !== false) {
                    $this->addDetectedTechnology('IIS', 'header', 90);
                }
            }
            
            // Controlla tecnologie da X-Powered-By
            if (isset($this->headers['X-Powered-By'])) {
                $powered = $this->headers['X-Powered-By'];
                if (stripos($powered, 'PHP') !== false) {
                    $this->addDetectedTechnology('PHP', 'header', 90);
                } else if (stripos($powered, 'ASP.NET') !== false) {
                    $this->addDetectedTechnology('ASP.NET', 'header', 90);
                }
            }
            
            // Crea risultati base con tecnologie rilevate
            $this->results = [
                'technologies' => $this->detectedTechnologies,
                'categories' => [],
                'scores' => [
                    'modernity' => 60,
                    'diversity' => 50,
                    'robustness' => 60,
                    'maintainability' => 60
                ],
                'totalScore' => 60,
                'fallbackUsed' => true,
                'error' => $this->errorMessage
            ];
            
            return true;
        }
        
        // Se non è possibile estrarre nulla, crea risultati vuoti
        $this->results = [
            'technologies' => [],
            'categories' => [],
            'scores' => [
                'modernity' => 50,
                'diversity' => 50,
                'robustness' => 50,
                'maintainability' => 50
            ],
            'totalScore' => 50,
            'error' => $this->errorMessage,
            'fallbackUsed' => true
        ];
        
        return true;
    }
}