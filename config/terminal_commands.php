<?php

return [

    'groups' => [
        'wordpress' => [
            'label' => 'WordPress / WP-CLI',
            'commands' => [
                'wp core version',
                'wp plugin list',
                'wp theme list',
                'wp cache flush',
                'wp rewrite flush',
                'wp user list',
            ],
        ],
        'docker' => [
            'label' => 'Docker',
            'commands' => [
                'docker ps',
                'docker compose ps',
                'pwd',
                'ls -la',
            ],
        ],
        'ubuntu' => [
            'label' => 'Ubuntu / Shell',
            'commands' => [
                'whoami',
                'php -v',
                'df -h',
                'free -m',
            ],
        ],
        'monitoring' => [
            'label' => 'Monitoring',
            'commands' => [
                'tail -n 100 /var/log/apache2/error.log 2>/dev/null || tail -n 100 /var/log/nginx/error.log 2>/dev/null || echo no log',
            ],
        ],
    ],

    'blocked_patterns' => [
        '/\brm\s+-rf\s+\/\s*$/i',
        '/\brm\s+-rf\s+\/\s/i',
        '/\bmkfs\b/i',
        '/\bshutdown\b/i',
        '/\breboot\b/i',
        '/\bdd\s+if=/i',
        '/:\(\)\s*\{\s*:\|\s*:\s*&\s*\}\s*;/',
        '/\bchmod\s+-R\s+777\s+\//i',
    ],

];
