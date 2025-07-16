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
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => $_ENV['REDIS_PORT'] ?? '6379',
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => 0,
        ],
    ],

    'ttl' => $_ENV['CACHE_TTL'] ?? 3600,
];