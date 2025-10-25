<?php

declare(strict_types=1);

namespace RagSystem\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RagSystem\Infrastructure\DependencyInjection\Container;
use RagSystem\Infrastructure\DependencyInjection\ServiceProvider;
use Mockery;

/**
 * Базовый класс для интеграционных тестов
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestEnvironment();
        $this->container = new Container();

        $this->container->bind(\Doctrine\DBAL\Connection::class, function() {
            return Mockery::mock(\Doctrine\DBAL\Connection::class);
        });

        $this->container->bind(\GuzzleHttp\Client::class, function() {
            return Mockery::mock(\GuzzleHttp\Client::class);
        });

        ServiceProvider::register($this->container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Настройка тестового окружения
     */
    private function setUpTestEnvironment(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DB_DATABASE'] = 'test_db';
        $_ENV['DB_USERNAME'] = 'test_user';
        $_ENV['DB_PASSWORD'] = 'test_password';
        $_ENV['DB_CONNECTION'] = 'postgresql';
        
        $_ENV['LLAMA_CPP_URL'] = 'http://localhost:8080';
        $_ENV['RABBITMQ_HOST'] = 'localhost';
        $_ENV['RABBITMQ_PORT'] = '5672';
        $_ENV['RABBITMQ_USER'] = 'guest';
        $_ENV['RABBITMQ_PASSWORD'] = 'guest';
        
        $_ENV['REDIS_HOST'] = 'localhost';
        $_ENV['REDIS_PORT'] = '6379';
        $_ENV['REDIS_PASSWORD'] = '';
        
        $_ENV['LOG_FILE'] = '/tmp/test.log';
        $_ENV['APP_DEBUG'] = 'true';

        $_ENV['VECTOR_SEARCH_THRESHOLD'] = '0.7';
        $_ENV['LLM_TIMEOUT'] = '30';
        $_ENV['CHUNK_SIZE'] = '1000';
        $_ENV['VECTOR_SEARCH_LIMIT'] = '10';
        $_ENV['REDIS_TASK_CACHE_PREFIX'] = 'test:';
        $_ENV['HYBRID_SEARCH_VECTOR_TOP_K'] = '20';
        $_ENV['HYBRID_SEARCH_KEYWORD_TOP_K'] = '20';
        $_ENV['HYBRID_SEARCH_RRF_K'] = '60';
    }

    /**
     * Переопределяет зависимость в контейнере
     */
    protected function bindMock(string $interface, callable $factory): void
    {
        $this->container->bind($interface, $factory);
    }

    /**
     * Получает сервис из контейнера
     */
    protected function getService(string $service): mixed
    {
        return $this->container->get($service);
    }
}
