<?php
/**
 * Service Configuration
 * 
 * Configurazione per i vari servizi utilizzati dall'applicazione
 */

return [
    // Configurazione API esterne
    'api' => [
        'pagespeed' => [
            'url' => 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed',
            'params' => [
                'strategy' => 'mobile',
                'category' => 'performance',
                'locale' => 'it_IT'
            ],
            'cache_ttl' => 86400 // 24 ore
        ],
        'moz' => [
            'url' => 'https://lsapi.seomoz.com/v2/url_metrics',
            'cache_ttl' => 86400 // 24 ore
        ],
        'securityheaders' => [
            'url' => 'https://securityheaders.com/api/v1/analyze',
            'cache_ttl' => 43200 // 12 ore
        ],
        'whois' => [
            'url' => 'https://www.whoisxmlapi.com/whoisserver/WhoisService',
            'params' => [
                'outputFormat' => 'JSON'
            ],
            'cache_ttl' => 604800 // 7 giorni
        ],
        'openai' => [
            // L'URL sarà determinato dinamicamente in base al modello specificato
            'url' => 'https://api.openai.com/v1/chat/completions', // Default per modelli chat
            'model' => 'gpt-3.5-turbo',           // Modello più recente e capace
            'temperature' => 0.7,
            'max_tokens' => 150,
            'cache_ttl' => 604800 // 7 giorni
        ],
        'w3c_html' => [
            'url' => 'https://validator.w3.org/nu/',
            'params' => [
                'out' => 'json'
            ],
            'cache_ttl' => 86400 // 24 ore
        ],
        'w3c_css' => [
            'url' => 'https://jigsaw.w3.org/css-validator/validator',
            'params' => [
                'output' => 'json',
                'profile' => 'css3'
            ],
            'cache_ttl' => 86400 // 24 ore
        ]
    ],
    
    // Configurazione rate limiting
    'rate_limits' => [
        'pagespeed' => [
            'limit' => 100,
            'period' => 86400 // 24 ore
        ],
        'moz' => [
            'limit' => 10,
            'period' => 3600 // 1 ora
        ],
        'securityheaders' => [
            'limit' => 50,
            'period' => 3600 // 1 ora
        ],
        'whois' => [
            'limit' => 100,
            'period' => 86400 // 24 ore
        ],
        'openai' => [
            'limit' => 20,
            'period' => 3600 // 1 ora
        ]
    ],
    
    // Configurazione sistema punteggio
    'scoring' => [
        'weights' => [
            'performance' => 0.3,   // 30%
            'seo' => 0.25,          // 25%
            'security' => 0.25,     // 25%
            'technical' => 0.2      // 20%
        ]
    ],
    
    // Configurazione sistema cache
    'cache' => [
        'enabled' => true,
        'path' => __DIR__ . '/../cache/data',
        'default_ttl' => 3600 // 1 ora
    ]
];