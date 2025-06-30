<?php
/**
 * SecurityAnalyzer
 * 
 * Analizzatore specializzato per gli aspetti di sicurezza di un sito web.
 * Valuta elementi come header HTTP di sicurezza, configurazione SSL/TLS,
 * presenza di informazioni sensibili e altre vulnerabilità comuni.
 * 
 * Pattern implementati:
 * - Strategy
 * - Template Method
 */

require_once __DIR__ . '/BaseAnalyzer.php';
require_once __DIR__ . '/../../core/ServiceFactory.php';

class SecurityAnalyzer extends BaseAnalyzer {
    /**
     * @var ServiceFactory Factory per servizi
     */
    private $serviceFactory;
    
    /**
     * @var array Header di sicurezza da verificare
     */
    private $securityHeaders = [
        'Strict-Transport-Security',
        'Content-Security-Policy',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-XSS-Protection',
        'Referrer-Policy',
        'Feature-Policy',
        'Permissions-Policy',
        'Expect-CT'
    ];
    
    /**
     * @var array Pattern per la ricerca di vulnerabilità comuni
     */
    private $vulnerabilityPatterns = [
        'error_exposure' => '/(SQL syntax|mysql_|mysqli_|ORA-[0-9]|syntax error|unclosed quotation mark|PostgreSQL|MySQL|Query failed:|Incorrect syntax near|ODBC Driver|MySQL Query failed)/i',
        'directory_traversal' => '/(\.\.\/|\.\.\\\\|%2e%2e%2f|%252e%252e%252f)/i',
        'csrf_tokens' => '/(<form[^>]*>(?:(?!<input[^>]*?csrf|<input[^>]*?token|<input[^>]*?nonce).)*<\/form>)/is',
        'server_info' => '/(Apache\/[0-9\.]+|nginx\/[0-9\.]+|PHP\/[0-9\.]+|IIS\/[0-9\.]+|ASP\.NET)/i',
        'sensitive_files' => '/(\.git\/|\.svn\/|\.env|config\.php|wp-config\.php|configuration\.php|config\.inc\.php)/i',
        'plaintext_auth' => '/(<form[^>]*>(?:(?!action="https:\/\/).)*(?:login|password|auth).*<\/form>)/is'
    ];
    
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
     * Esegue l'analisi di sicurezza specifica
     */
    protected function doAnalyze() {
        // Analizza gli header di sicurezza
        $this->analyzeSecurityHeaders();
        
        // Analizza la configurazione SSL/TLS
        $this->analyzeSSL();
        
        // Analizza le vulnerabilità comuni nel DOM
        $this->analyzeVulnerabilities();
        
        // Analizza i permessi dei cookie
        $this->analyzeCookies();
        
        // Analizza i form e il metodo di autenticazione
        $this->analyzeAuthForms();
        
        // Analizza gli elementi che potrebbero esporre informazioni sensibili
        $this->analyzeSensitiveInfo();
        
        // Tenta di integrare dati da API esterne
        $this->integrateExternalData();
        
        // Calcola i punteggi finali
        $this->calculateScores();
    }
    
    /**
     * Analizza gli header di sicurezza
     */
    private function analyzeSecurityHeaders() {
        // Inizializza i risultati per gli header di sicurezza
        $this->results['securityHeaders'] = [
            'present' => [],
            'missing' => [],
            'values' => [],
            'score' => 0
        ];
        
        // Verifica la presenza degli header di sicurezza
        foreach ($this->securityHeaders as $header) {
            $headerFound = false;
            
            // Cerca l'header (case-insensitive)
            foreach ($this->headers as $key => $value) {
                if (strcasecmp($key, $header) === 0) {
                    $headerFound = true;
                    $this->results['securityHeaders']['present'][] = $header;
                    $this->results['securityHeaders']['values'][$header] = $value;
                    break;
                }
            }
            
            if (!$headerFound) {
                $this->results['securityHeaders']['missing'][] = $header;
            }
        }
        
        // Calcola il punteggio per gli header di sicurezza
        $totalHeaders = count($this->securityHeaders);
        $presentHeaders = count($this->results['securityHeaders']['present']);
        
        $baseScore = ($presentHeaders / $totalHeaders) * 100;
        
        // Bonus per header critici
        if (in_array('Strict-Transport-Security', $this->results['securityHeaders']['present'])) {
            $baseScore += 5;
        }
        
        if (in_array('Content-Security-Policy', $this->results['securityHeaders']['present'])) {
            $baseScore += 10;
        }
        
        if (in_array('X-Frame-Options', $this->results['securityHeaders']['present'])) {
            $baseScore += 5;
        }
        
        if (in_array('X-XSS-Protection', $this->results['securityHeaders']['present'])) {
            $baseScore += 5;
        }
        
        $this->results['securityHeaders']['score'] = min(100, $baseScore);
    }
    
    /**
     * Analizza la configurazione SSL/TLS
     */
    private function analyzeSSL() {
        // Inizializza i risultati per SSL/TLS
        $this->results['ssl'] = [
            'enabled' => false,
            'protocol' => null,
            'cipher' => null,
            'certificate' => [
                'valid' => false,
                'issuer' => null,
                'validFrom' => null,
                'validTo' => null,
                'daysRemaining' => 0
            ],
            'vulnerabilities' => [],
            'score' => 0
        ];
        
        // Verifica se HTTPS è abilitato
        $parsedUrl = parse_url($this->url);
        $this->results['ssl']['enabled'] = ($parsedUrl['scheme'] === 'https');
        
        if (!$this->results['ssl']['enabled']) {
            $this->results['ssl']['score'] = 0;
            return;
        }
        
        // Ottieni informazioni sul certificato SSL
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $host = $parsedUrl['host'];
        $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : 443;
        
        try {
            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                5,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if ($socket) {
                $params = stream_context_get_params($socket);
                
                if (isset($params['options']['ssl']['peer_certificate'])) {
                    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                    
                    if ($cert) {
                        $this->results['ssl']['certificate']['valid'] = true;
                        $this->results['ssl']['certificate']['issuer'] = $cert['issuer']['CN'] ?? 'Unknown';
                        $this->results['ssl']['certificate']['validFrom'] = date('Y-m-d', $cert['validFrom_time_t']);
                        $this->results['ssl']['certificate']['validTo'] = date('Y-m-d', $cert['validTo_time_t']);
                        
                        // Calcola i giorni rimanenti
                        $daysRemaining = ceil(($cert['validTo_time_t'] - time()) / 86400);
                        $this->results['ssl']['certificate']['daysRemaining'] = max(0, $daysRemaining);
                    }
                }
                
                // Ottieni informazioni sul protocollo e il cipher
                $metaData = stream_get_meta_data($socket);
                
                if (isset($metaData['crypto'])) {
                    $crypto = $metaData['crypto'];
                    $this->results['ssl']['protocol'] = $crypto['protocol'] ?? null;
                    $this->results['ssl']['cipher'] = $crypto['cipher_name'] ?? null;
                }
                
                stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
            }
        } catch (Exception $e) {
            // In caso di errore, continua con le informazioni già raccolte
        }
        
        // Verifica vulnerabilità SSL comuni
        $vulnerabilities = [];
        
        // Protocolli obsoleti (< TLS 1.2)
        if ($this->results['ssl']['protocol'] && 
            (strpos($this->results['ssl']['protocol'], 'SSLv') !== false || 
             strpos($this->results['ssl']['protocol'], 'TLSv1.0') !== false || 
             strpos($this->results['ssl']['protocol'], 'TLSv1.1') !== false)) {
            $vulnerabilities[] = 'Protocollo obsoleto: ' . $this->results['ssl']['protocol'];
        }
        
        // Cipher deboli
        $weakCiphers = ['RC4', 'DES', '3DES', 'MD5', 'NULL'];
        foreach ($weakCiphers as $weakCipher) {
            if ($this->results['ssl']['cipher'] && strpos($this->results['ssl']['cipher'], $weakCipher) !== false) {
                $vulnerabilities[] = 'Cipher debole: ' . $this->results['ssl']['cipher'];
                break;
            }
        }
        
        $this->results['ssl']['vulnerabilities'] = $vulnerabilities;
        
        // Calcola il punteggio SSL
        $score = 70; // Punteggio base per HTTPS
        
        // Certificato valido e non scaduto
        if ($this->results['ssl']['certificate']['valid']) {
            $score += 10;
            
            // Bonus per certificato con lunga validità rimanente
            if ($this->results['ssl']['certificate']['daysRemaining'] > 90) {
                $score += 5;
            } elseif ($this->results['ssl']['certificate']['daysRemaining'] < 30) {
                $score -= 10; // Penalità per certificato in scadenza
            }
        } else {
            $score -= 30; // Penalità per certificato non valido
        }
        
        // Protocollo moderno (TLS 1.2+)
        if ($this->results['ssl']['protocol'] && 
            (strpos($this->results['ssl']['protocol'], 'TLSv1.2') !== false || 
             strpos($this->results['ssl']['protocol'], 'TLSv1.3') !== false)) {
            $score += 10;
        }
        
        // Penalità per vulnerabilità
        $score -= count($vulnerabilities) * 15;
        
        $this->results['ssl']['score'] = min(100, max(0, $score));
    }
    
    /**
     * Analizza le vulnerabilità comuni nel DOM
     */
    private function analyzeVulnerabilities() {
        // Inizializza i risultati per le vulnerabilità
        $this->results['vulnerabilities'] = [
            'found' => [],
            'count' => 0,
            'details' => [],
            'score' => 100 // Punteggio iniziale: 100 (ottimo)
        ];
        
        // Verifica ciascun pattern di vulnerabilità
        foreach ($this->vulnerabilityPatterns as $type => $pattern) {
            preg_match_all($pattern, $this->pageContent, $matches);
            
            $matchCount = count($matches[0]);
            
            if ($matchCount > 0) {
                $this->results['vulnerabilities']['found'][] = $type;
                $this->results['vulnerabilities']['count'] += $matchCount;
                
                // Memorizza i dettagli (limita il numero di esempi per non appesantire i risultati)
                $examples = array_slice($matches[0], 0, 3);
                $this->results['vulnerabilities']['details'][$type] = [
                    'count' => $matchCount,
                    'examples' => $examples
                ];
                
                // Riduci il punteggio in base al tipo di vulnerabilità
                switch ($type) {
                    case 'error_exposure':
                        $this->results['vulnerabilities']['score'] -= 15;
                        break;
                    case 'directory_traversal':
                        $this->results['vulnerabilities']['score'] -= 20;
                        break;
                    case 'csrf_tokens':
                        $this->results['vulnerabilities']['score'] -= 10 * min(3, $matchCount);
                        break;
                    case 'server_info':
                        $this->results['vulnerabilities']['score'] -= 5;
                        break;
                    case 'sensitive_files':
                        $this->results['vulnerabilities']['score'] -= 15;
                        break;
                    case 'plaintext_auth':
                        $this->results['vulnerabilities']['score'] -= 20;
                        break;
                }
            }
        }
        
        // Assicura che il punteggio non scenda sotto lo zero
        $this->results['vulnerabilities']['score'] = max(0, $this->results['vulnerabilities']['score']);
    }
    
    /**
     * Analizza i permessi dei cookie
     */
    private function analyzeCookies() {
        // Inizializza i risultati per i cookie
        $this->results['cookies'] = [
            'total' => 0,
            'secure' => 0,
            'httpOnly' => 0,
            'sameSite' => 0,
            'score' => 0
        ];
        
        // Estrai i cookie dalle intestazioni
        $cookies = [];
        
        foreach ($this->headers as $header => $value) {
            if (strcasecmp($header, 'Set-Cookie') === 0) {
                if (is_array($value)) {
                    foreach ($value as $cookie) {
                        $cookies[] = $cookie;
                    }
                } else {
                    $cookies[] = $value;
                }
            }
        }
        
        $this->results['cookies']['total'] = count($cookies);
        
        // Analizza le proprietà di sicurezza di ciascun cookie
        foreach ($cookies as $cookie) {
            // Flag Secure
            if (stripos($cookie, 'secure') !== false) {
                $this->results['cookies']['secure']++;
            }
            
            // Flag HttpOnly
            if (stripos($cookie, 'httponly') !== false) {
                $this->results['cookies']['httpOnly']++;
            }
            
            // Attributo SameSite
            if (preg_match('/samesite\s*=\s*(strict|lax|none)/i', $cookie)) {
                $this->results['cookies']['sameSite']++;
            }
        }
        
        // Calcola le percentuali
        if ($this->results['cookies']['total'] > 0) {
            $this->results['cookies']['securePercentage'] = round(($this->results['cookies']['secure'] / $this->results['cookies']['total']) * 100);
            $this->results['cookies']['httpOnlyPercentage'] = round(($this->results['cookies']['httpOnly'] / $this->results['cookies']['total']) * 100);
            $this->results['cookies']['sameSitePercentage'] = round(($this->results['cookies']['sameSite'] / $this->results['cookies']['total']) * 100);
        } else {
            $this->results['cookies']['securePercentage'] = 0;
            $this->results['cookies']['httpOnlyPercentage'] = 0;
            $this->results['cookies']['sameSitePercentage'] = 0;
        }
        
        // Calcola il punteggio per i cookie
        if ($this->results['cookies']['total'] > 0) {
            $score = (
                ($this->results['cookies']['securePercentage'] * 0.4) +
                ($this->results['cookies']['httpOnlyPercentage'] * 0.4) +
                ($this->results['cookies']['sameSitePercentage'] * 0.2)
            );
        } else {
            // Se non ci sono cookie, assegna un punteggio neutro
            $score = 50;
        }
        
        $this->results['cookies']['score'] = min(100, $score);
    }
    
    /**
     * Analizza i form e il metodo di autenticazione
     */
    private function analyzeAuthForms() {
        // Inizializza i risultati per i form di autenticazione
        $this->results['authForms'] = [
            'total' => 0,
            'secure' => 0,
            'insecure' => 0,
            'details' => [],
            'score' => 0
        ];
        
        // Trova tutti i form
        $forms = [];
        $formNodes = $this->dom->getElementsByTagName('form');
        
        foreach ($formNodes as $form) {
            $formData = [
                'action' => $form->getAttribute('action'),
                'method' => strtoupper($form->getAttribute('method') ?: 'GET'),
                'hasPasswordField' => false,
                'hasCSRFToken' => false,
                'isSecure' => false
            ];
            
            // Cerca campi password
            $inputs = $form->getElementsByTagName('input');
            foreach ($inputs as $input) {
                $type = $input->getAttribute('type');
                $name = $input->getAttribute('name');
                
                if ($type === 'password') {
                    $formData['hasPasswordField'] = true;
                }
                
                // Verifica la presenza di token CSRF
                if (preg_match('/(csrf|token|nonce)/i', $name)) {
                    $formData['hasCSRFToken'] = true;
                }
            }
            
            // Verifica se il form è protetto
            if ($formData['hasPasswordField']) {
                $this->results['authForms']['total']++;
                
                // Controlla se l'azione è sicura (HTTPS)
                $action = $formData['action'];
                
                if (empty($action)) {
                    // Se l'azione è vuota, usa l'URL corrente
                    $action = $this->url;
                } elseif (strpos($action, 'http') !== 0) {
                    // Se è relativa, costruisci l'URL completo
                    $action = $this->getFullUrl($action);
                }
                
                $formData['fullAction'] = $action;
                $formData['isSecure'] = (strpos($action, 'https://') === 0);
                
                if ($formData['isSecure'] && $formData['method'] === 'POST' && $formData['hasCSRFToken']) {
                    $this->results['authForms']['secure']++;
                } else {
                    $this->results['authForms']['insecure']++;
                    $formData['issues'] = [];
                    
                    if (!$formData['isSecure']) {
                        $formData['issues'][] = 'Non utilizza HTTPS';
                    }
                    
                    if ($formData['method'] !== 'POST') {
                        $formData['issues'][] = 'Non utilizza il metodo POST';
                    }
                    
                    if (!$formData['hasCSRFToken']) {
                        $formData['issues'][] = 'Manca protezione CSRF';
                    }
                }
                
                $this->results['authForms']['details'][] = $formData;
            }
        }
        
        // Calcola il punteggio per i form di autenticazione
        if ($this->results['authForms']['total'] > 0) {
            $score = ($this->results['authForms']['secure'] / $this->results['authForms']['total']) * 100;
        } else {
            // Se non ci sono form di autenticazione, assegna un punteggio neutro
            $score = 50;
        }
        
        $this->results['authForms']['score'] = min(100, $score);
    }
    
    /**
     * Analizza gli elementi che potrebbero esporre informazioni sensibili
     */
    private function analyzeSensitiveInfo() {
        // Inizializza i risultati per le informazioni sensibili
        $this->results['sensitiveInfo'] = [
            'found' => [],
            'count' => 0,
            'score' => 100 // Punteggio iniziale: 100 (ottimo)
        ];
        
        // Pattern per le informazioni sensibili
        $sensitivePatterns = [
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i',
            'phone' => '/(?:\+\d{1,3}[-\s]?)?(?:\(\d{1,4}\)|\d{1,4})[-\s]?\d{1,9}[-\s]?\d{1,9}/i',
            'api_key' => '/(api[_-]?key|apikey|token)["\']?\s*[:=]\s*["\']([\w\-]{12,})/i',
            'private_key' => '/-----BEGIN\s+(?:RSA\s+)?PRIVATE\s+KEY\s*-----/i',
            'connection_string' => '/(mongodb|mysql|postgresql|sqlserver|jdbc):\/\/\S+/i',
            'ip_address' => '/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/i',
            'credit_card' => '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|6(?:011|5[0-9]{2})[0-9]{12}|(?:2131|1800|35\d{3})\d{11})\b/i'
        ];
        
        // Verifica la presenza di informazioni sensibili
        foreach ($sensitivePatterns as $type => $pattern) {
            preg_match_all($pattern, $this->pageContent, $matches);
            
            $matchCount = count($matches[0]);
            
            if ($matchCount > 0) {
                $this->results['sensitiveInfo']['found'][] = $type;
                $this->results['sensitiveInfo']['count'] += $matchCount;
                
                // Penalità in base al tipo di informazione esposta
                switch ($type) {
                    case 'email':
                        // Meno grave, spesso gli indirizzi email sono pubblici
                        $this->results['sensitiveInfo']['score'] -= min(5, $matchCount);
                        break;
                    case 'phone':
                        $this->results['sensitiveInfo']['score'] -= min(10, $matchCount * 2);
                        break;
                    case 'api_key':
                    case 'private_key':
                    case 'connection_string':
                        // Molto grave
                        $this->results['sensitiveInfo']['score'] -= 40;
                        break;
                    case 'ip_address':
                        $this->results['sensitiveInfo']['score'] -= min(10, $matchCount);
                        break;
                    case 'credit_card':
                        // Estremamente grave
                        $this->results['sensitiveInfo']['score'] -= 50;
                        break;
                }
            }
        }
        
        // Limita il punteggio a un minimo di 0
        $this->results['sensitiveInfo']['score'] = max(0, $this->results['sensitiveInfo']['score']);
    }
    
    /**
     * Integra dati da API esterne
     */
    private function integrateExternalData() {
        try {
            error_log("[SECURITY] Avvio integrazione dati esterni da Security Headers API per URL: {$this->url}");
            
            // Utilizza il servizio di proxy per integrare dati da API esterne
            $domain = parse_url($this->url, PHP_URL_HOST);
            
            $proxyService = $this->serviceFactory->createService('proxy', [
                'service' => 'securityheaders',
                'timeout' => 10,
                'params' => [
                    'domain' => $domain,
                    'hide' => 'on' // Nasconde i risultati dall'elenco pubblico
                ]
            ]);
            
            error_log("[SECURITY] Esecuzione chiamata a Security Headers API per {$domain}");
            $success = $proxyService->execute();
            
            if ($success && !$proxyService->hasError()) {
                error_log("[SECURITY] Chiamata a Security Headers API completata con successo");
                $securityData = $proxyService->getResult();
                
                if (is_array($securityData)) {
                    error_log("[SECURITY] Dati Security Headers integrati: " . json_encode(array_keys($securityData)));
                    $this->results['external'] = $securityData;
                    
                    // Integra i dati esterni nel punteggio se disponibili
                    if (isset($securityData['grade'])) {
                        $grade = $securityData['grade'];
                        error_log("[SECURITY] Voto Security Headers: {$grade}");
                        
                        $gradeScores = [
                            'A+' => 100,
                            'A' => 95,
                            'A-' => 90,
                            'B+' => 85,
                            'B' => 80,
                            'B-' => 75,
                            'C+' => 70,
                            'C' => 65,
                            'C-' => 60,
                            'D+' => 55,
                            'D' => 50,
                            'D-' => 45,
                            'E+' => 40,
                            'E' => 35,
                            'E-' => 30,
                            'F' => 20
                        ];
                        
                        if (isset($gradeScores[$grade])) {
                            // Combina il punteggio esterno con quello calcolato
                            $this->results['external']['score'] = $gradeScores[$grade];
                            error_log("[SECURITY] Punteggio convertito: {$gradeScores[$grade]}");
                        }
                    }
                }
            } else {
                error_log("[SECURITY ERROR] Errore chiamata Security Headers API: " . $proxyService->getErrorMessage());
            }
        } catch (Exception $e) {
            // In caso di errore, continua senza dati esterni
            error_log("[SECURITY ERROR] Eccezione durante l'integrazione dati esterni: " . $e->getMessage());
        }
    }
    
    /**
     * Calcola i punteggi finali
     */
    private function calculateScores() {
        // Pesi per le diverse categorie
        $weights = [
            'securityHeaders' => 0.25,
            'ssl' => 0.25,
            'vulnerabilities' => 0.20,
            'cookies' => 0.10,
            'authForms' => 0.10,
            'sensitiveInfo' => 0.10
        ];
        
        // Se sono disponibili dati esterni, aggiungi anche quelli con un peso elevato
        if (isset($this->results['external']['score'])) {
            $weights['external'] = 0.20;
            
            // Normalizza i pesi
            $totalWeight = array_sum($weights);
            foreach ($weights as $category => $weight) {
                $weights[$category] = $weight / $totalWeight;
            }
        }
        
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
            'securityHeaders' => ['score' => 0],
            'ssl' => ['score' => 0],
            'vulnerabilities' => ['score' => 0],
            'cookies' => ['score' => 0],
            'authForms' => ['score' => 0],
            'sensitiveInfo' => ['score' => 0],
            'totalScore' => 0,
            'error' => $this->errorMessage
        ];
        
        return true;
    }
}