<?php

declare(strict_types=1);

/**
 * Файл инициализации для тестов PHPUnit
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('UTC');

define('APP_ENV', 'testing');
define('APP_DEBUG', true);


require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env.testing')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.testing');
    $dotenv->load();
} else {
    $_ENV['APP_ENV'] = 'testing';
    $_ENV['APP_DEBUG'] = 'true';
    $_ENV['LLAMA_CPP_URL'] = 'http://localhost:8080';
    $_ENV['RABBITMQ_HOST'] = 'localhost';
    $_ENV['RABBITMQ_PORT'] = '5672';
    $_ENV['RABBITMQ_USER'] = 'guest';
    $_ENV['RABBITMQ_PASSWORD'] = 'guest';
}

require_once __DIR__ . '/Helpers/BaseTestCase.php';
require_once __DIR__ . '/Helpers/TestDataFactory.php';
require_once __DIR__ . '/Helpers/MockLlamaCppAdapter.php';
require_once __DIR__ . '/Helpers/MockRabbitMQService.php';
require_once __DIR__ . '/Helpers/MockDatabaseConnection.php';

if (file_exists(__DIR__ . '/Fixtures/test_data.php')) {
    require_once __DIR__ . '/Fixtures/test_data.php';
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    echo "Неперехваченное исключение: " . $exception->getMessage() . "\n";
    echo "Трассировка стека:\n" . $exception->getTraceAsString() . "\n";
});

echo "Тестовое окружение запущено\n";
