<?php
/**
 * Security Utils Advanced
 * 
 * Funzioni di utilità avanzate per la sicurezza dell'applicazione.
 * Implementa sanitizzazione input, protezione CSRF, validazione URL,
 * rate limiting, e altre misure di sicurezza.
 */

class Security {
    /**
     * @var array Lista di indirizzi IP privati da bloccare
     */
    private static $privateIpRanges = [
        '10.0.0.0/8',         // RFC1918
        '172.16.0.0/12',      // RFC1918
        '192.168.0.0/16',     // RFC1918
        '169.254.0.0/16',     // RFC3927 (Link-Local)
        '127.0.0.0/8',        // RFC1122 (Localhost)
        '0.0.0.0/8',          // RFC1122 (Broadcast)
        '::1/128',            // IPv6 Localhost
        'fe80::/10',          // IPv6 Link-Local
        'fc00::/7'            // IPv6 Unique-Local
    ];
    
    /**
     * @var array Intestazioni di sicurezza configurabili
     */
    private static $securityHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload'
    ];
    
    /**
     * Sanitizza un array di input
     * 
     * @param array $input Array di input da sanitizzare
     * @param bool $strict Se usare sanitizzazione rigorosa
     * @return array Input sanitizzato
     */
    public static function sanitizeInput($input, $strict = false) {
        if (!is_array($input)) {
            return self::sanitizeValue($input, $strict ? 'string' : null);
        }
        
        $sanitized = [];
        foreach ($input as $key => $value) {
            // Sanitizza la chiave
            $sanitizedKey = self::sanitizeKey($key);
            
            // Determina il tipo di dato per sanitizzazione rigorosa
            $type = null;
            if ($strict && isset($input['_types'][$key])) {
                $type = $input['_types'][$key];
            }
            
            // Sanitizza il valore (ricorsivamente se è un array)
            $sanitized[$sanitizedKey] = is_array($value) 
                ? self::sanitizeInput($value, $strict) 
                : self::sanitizeValue($value, $type);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitizza una singola chiave
     * 
     * @param string $key Chiave da sanitizzare
     * @return string Chiave sanitizzata
     */
    private static function sanitizeKey($key) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    }
    
    /**
     * Sanitizza un singolo valore con supporto per tipi specifici
     * 
     * @param mixed $value Valore da sanitizzare
     * @param string|null $type Tipo di dato ('string', 'int', 'float', 'url', 'email', 'html')
     * @return mixed Valore sanitizzato
     */
    public static function sanitizeValue($value, $type = null) {
        if ($value === null) {
            return null;
        }
        
        if (is_string($value)) {
            switch ($type) {
                case 'url':
                    // Sanitizza URL
                    $value = filter_var($value, FILTER_SANITIZE_URL);
                    // Verifica schema consentito
                    $scheme = parse_url($value, PHP_URL_SCHEME);
                    if (!in_array($scheme, ['http', 'https'])) {
                        return '';
                    }
                    break;
                    
                case 'email':
                    $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                    break;
                    
                case 'int':
                    $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                    break;
                    
                case 'float':
                    $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    break;
                    
                case 'html':
                    // Purificazione HTML di base - in un sistema di produzione, utilizzare una libreria come HTMLPurifier
                    $allowedTags = '<p><a><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><img>';
                    $value = strip_tags($value, $allowedTags);
                    
                    // Rimuovi attributi potenzialmente pericolosi
                    $value = preg_replace('/(<[^>]+)(?:\s|\t|\n)on\w+=".+?"/i', '$1', $value); // on* event handlers
                    $value = preg_replace('/(<[^>]+)(?:\s|\t|\n)javascript:.+?(?:\s|"|\'|>)/i', '$1', $value); // javascript: URLs
                    break;
                    
                case 'string':
                default:
                    // Sostituisco FILTER_SANITIZE_STRING (deprecato in PHP 8.1+) con alternative
                    $value = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
                    break;
            }
        } elseif ($type !== null) {
            // Conversione di tipo se richiesta
            switch ($type) {
                case 'int':
                    $value = (int)$value;
                    break;
                case 'float':
                    $value = (float)$value;
                    break;
                case 'bool':
                    $value = (bool)$value;
                    break;
                case 'string':
                    $value = (string)$value;
                    break;
            }
        }
        
        return $value;
    }
    
    /**
     * Verifica che un URL sia valido e sicuro
     * 
     * @param string $url URL da verificare
     * @param bool $checkReachable Verifica se l'URL è raggiungibile
     * @return bool|string True se l'URL è valido, messaggio di errore altrimenti
     */
    public static function isValidUrl($url, $checkReachable = false) {
        if (empty($url)) {
            return "L'URL è vuoto";
        }
        
        // Sanitizza l'input
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Controlla se è un URL valido
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return "L'URL non è in un formato valido";
        }
        
        // Controlla che lo schema sia http o https
        $urlParts = parse_url($url);
        $scheme = $urlParts['scheme'] ?? '';
        
        if (!in_array($scheme, ['http', 'https'])) {
            return "Solo gli schemi HTTP e HTTPS sono supportati";
        }
        
        // Verifica che non sia un IP privato
        $host = $urlParts['host'] ?? '';
        if (self::isPrivateIP($host)) {
            return "Gli indirizzi IP privati non sono consentiti";
        }
        
        // Verifica opzionale di raggiungibilità
        if ($checkReachable) {
            $result = self::checkUrlReachable($url);
            if ($result !== true) {
                return $result;
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se un host è un indirizzo IP privato
     * 
     * @param string $host Hostname o indirizzo IP
     * @return bool True se è un IP privato
     */
    public static function isPrivateIP($host) {
        // Se è un nome di dominio, prova a risolverlo
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            $ips = gethostbynamel($host);
            if (!$ips) {
                return false; // Non è stato possibile risolvere, assumiamo pubblico
            }
            $host = $ips[0]; // Usa il primo IP
        }
        
        // Verifica IPv4
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // Converti IP in numero long
            $longIP = ip2long($host);
            
            // Range IP privati come da RFC1918
            return (
                ($longIP >= ip2long('10.0.0.0') && $longIP <= ip2long('10.255.255.255')) ||
                ($longIP >= ip2long('172.16.0.0') && $longIP <= ip2long('172.31.255.255')) ||
                ($longIP >= ip2long('192.168.0.0') && $longIP <= ip2long('192.168.255.255')) ||
                ($longIP >= ip2long('127.0.0.0') && $longIP <= ip2long('127.255.255.255'))
            );
        }
        
        // Verifica IPv6
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6 localhost
            if ($host == '::1') {
                return true;
            }
            
            // Controlla se è un IPv6 locale
            $first2Bytes = substr(bin2hex(inet_pton($host)), 0, 4);
            return $first2Bytes == 'fc00' || $first2Bytes == 'fd00' || $first2Bytes == 'fe80';
        }
        
        return false;
    }
    
    /**
     * Verifica se un URL è raggiungibile
     * 
     * @param string $url URL da verificare
     * @param int $timeout Timeout in secondi
     * @return bool|string True se l'URL è raggiungibile, messaggio di errore altrimenti
     */
    public static function checkUrlReachable($url, $timeout = 5) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        
        // Verifica errori di connessione
        if (!empty($error)) {
            return "Errore di connessione: " . $error;
        }
        
        // Verifica HTTP status code
        if ($httpCode < 200 || $httpCode >= 400) {
            return "URL non raggiungibile (HTTP code: $httpCode)";
        }
        
        // Verifica che sia HTML/TEXT
        if ($contentType && !preg_match('/(text\/html|application\/xhtml\+xml)/i', $contentType)) {
            return "L'URL non restituisce contenuto HTML/XHTML";
        }
        
        return true;
    }
    
    /**
     * Genera un token CSRF con supporto double-submit cookie
     * 
     * @param bool $useCookie Usa anche cookie per double-submit verification
     * @return string Token CSRF
     */
    public static function generateCsrfToken($useCookie = true) {
        // Genera un token casuale
        $token = bin2hex(random_bytes(32));
        
        // Memorizza in sessione
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        // Imposta anche come cookie per double-submit verification
        if ($useCookie) {
            setcookie('csrf_token', $token, [
                'expires' => time() + 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => false, // False perché deve essere accessibile via JS
                'samesite' => 'Strict'
            ]);
        }
        
        return $token;
    }
    
    /**
     * Verifica un token CSRF con supporto double-submit cookie
     * 
     * @param string $token Token da verificare
     * @param bool $checkCookie Verifica anche il cookie
     * @param int $maxAge Età massima del token in secondi (default 1 ora)
     * @return bool True se il token è valido
     */
    public static function verifyCsrfToken($token, $checkCookie = true, $maxAge = 3600) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Verifica scadenza token
        if ((time() - $_SESSION['csrf_token_time']) > $maxAge) {
            return false;
        }
        
        // Verifica corrispondenza token sessione
        $validSession = hash_equals($_SESSION['csrf_token'], $token);
        
        // Verifica corrispondenza token cookie (double-submit verification)
        $validCookie = true;
        if ($checkCookie) {
            $cookieToken = $_COOKIE['csrf_token'] ?? '';
            $validCookie = hash_equals($_SESSION['csrf_token'], $cookieToken);
        }
        
        return $validSession && $validCookie;
    }
    
    /**
     * Imposta le intestazioni di sicurezza per prevenire XSS, clickjacking, ecc.
     * 
     * @param array $customHeaders Intestazioni personalizzate per sovrascrivere quelle predefinite
     * @param bool $csp Abilita Content-Security-Policy
     */
    public static function setSecurityHeaders($customHeaders = [], $csp = true) {
        // Unisce le intestazioni personalizzate con quelle predefinite
        $headers = array_merge(self::$securityHeaders, $customHeaders);
        
        // Imposta le intestazioni
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        
        // Imposta Content-Security-Policy se abilitato
        if ($csp) {
            $cspDirectives = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "img-src 'self' data:",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'"
            ];
            
            header("Content-Security-Policy: " . implode("; ", $cspDirectives));
        }
    }
    
    /**
     * Crea un token JWT per autenticazione API
     * 
     * @param array $payload Dati payload
     * @param int $expiry Durata in secondi
     * @return string Token JWT
     */
    public static function createJwtToken($payload, $expiry = 3600) {
        // Header JWT
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        // Payload con expiry
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();
        $payload['jti'] = bin2hex(random_bytes(16)); // JWT ID unico
        
        // Codifica header e payload
        $encodedHeader = base64_encode(json_encode($header));
        $encodedPayload = base64_encode(json_encode($payload));
        
        // Ottieni la chiave segreta
        $config = require __DIR__ . '/../config/services.php';
        $secret = $config['security']['jwt_secret'] ?? 'default_unsafe_secret';
        
        // Crea signature
        $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $secret, true);
        $encodedSignature = base64_encode($signature);
        
        return "$encodedHeader.$encodedPayload.$encodedSignature";
    }
    
    /**
     * Verifica token JWT
     * 
     * @param string $token Token JWT
     * @return array|false Payload o false se non valido
     */
    public static function verifyJwtToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($encodedHeader, $encodedPayload, $encodedSignature) = $parts;
        
        // Ottieni la chiave segreta
        $config = require __DIR__ . '/../config/services.php';
        $secret = $config['security']['jwt_secret'] ?? 'default_unsafe_secret';
        
        // Verifica signature
        $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", $secret, true);
        $expectedSignature = base64_encode($signature);
        
        if (!hash_equals($encodedSignature, $expectedSignature)) {
            return false;
        }
        
        // Decodifica payload
        $payload = json_decode(base64_decode($encodedPayload), true);
        
        // Verifica expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Controlla il rate limit in base a una chiave (es. IP)
     * 
     * @param string $key Chiave identificativa (es. indirizzo IP)
     * @param int $maxRequests Numero massimo di richieste consentite
     * @param int $timeWindow Finestra temporale in secondi
     * @return bool True se la richiesta è consentita
     */
    public static function checkRateLimit($key, $maxRequests = 100, $timeWindow = 60) {
        // Utilizza Cache per memorizzare il rate limit
        $cache = new Cache();
        $rateKey = "ratelimit:{$key}";
        
        // Ottieni lo stato attuale
        $current = $cache->get($rateKey) ?: [
            'count' => 0,
            'resetAt' => time() + $timeWindow
        ];
        
        // Resetta se la finestra temporale è scaduta
        if (time() > $current['resetAt']) {
            $current = [
                'count' => 0,
                'resetAt' => time() + $timeWindow
            ];
        }
        
        // Incrementa contatore
        $current['count']++;
        
        // Salva stato
        $cache->set($rateKey, $current, $timeWindow);
        
        // Aggiungi header con limiti (per API)
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . max(0, $maxRequests - $current['count']));
        header('X-RateLimit-Reset: ' . $current['resetAt']);
        
        // Imposta status code 429 se il limite è stato superato
        if ($current['count'] > $maxRequests) {
            header('HTTP/1.1 429 Too Many Requests');
            header('Retry-After: ' . ($current['resetAt'] - time()));
            return false;
        }
        
        return true;
    }
    
    /**
     * Genera e verifica una password hash sicura
     * 
     * @param string $password Password da hashare
     * @return string Password hashata
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Verifica una password contro un hash
     * 
     * @param string $password Password da verificare
     * @param string $hash Hash da confrontare
     * @return bool True se la password è corretta
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Genera una stringa casuale sicura
     * 
     * @param int $length Lunghezza della stringa
     * @return string Stringa casuale
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}