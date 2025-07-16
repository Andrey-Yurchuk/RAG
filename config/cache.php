<?php

declare(strict_types=1);

return [
    'default'  => 'redis',

    'stores'  => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
    ],

    'connections' => [
        'default' => [
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
            'password' => $_ENV['REDIS_PASSWORD'],
            'database' => 0,
        ],
    ],

    'ttl' => $_ENV['CACHE_TTL'] ?? 3600,
];