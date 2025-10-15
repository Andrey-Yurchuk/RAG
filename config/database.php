<?php

declare(strict_types=1);

require_once __DIR__ . '/dbal-types.php';

$postgresqlPlatformClass = require __DIR__ . '/postgresql-platform.php';

return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'postgresql',

    'connections' => [
        'postgresql' => [
            'driver' => 'pdo_pgsql',
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],

];
