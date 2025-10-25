<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\UI\Http\Controller;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\UI\Http\Controller\HealthController;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use Mockery;

/**
 * Тесты для HealthController
 * 
 * @covers \RagSystem\UI\Http\Controller\HealthController
 * @covers \RagSystem\Infrastructure\Http\Request
 * @covers \RagSystem\Infrastructure\Http\Response
 */
class HealthControllerTest extends BaseTestCase
{
    private HealthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $connection = Mockery::mock(\Doctrine\DBAL\Connection::class);
        $llamaCppAdapter = Mockery::mock(\RagSystem\Infrastructure\Service\LlamaCppAdapter::class);
        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $logger->shouldReceive('error')->andReturnSelf();
        $logger->shouldReceive('info')->andReturnSelf();
        
        $this->controller = new HealthController($connection, $llamaCppAdapter, $logger);
    }

    /**
     * Тест проверки здоровья системы
     */
    public function testHealthCheck(): void
    {
        $request = new Request('GET', '/health');
        $response = $this->controller->check($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type']);
        
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('healthy', $responseData['status']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    /**
     * Тест с русскими символами
     */
    public function testHealthCheckWithRussianText(): void
    {
        $request = new Request('GET', '/health');
        $response = $this->controller->check($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('healthy', $responseData['status']);
    }

    /**
     * Тест с различными методами запроса
     */
    public function testHealthCheckWithDifferentMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        
        foreach ($methods as $method) {
            $request = new Request($method, '/health');
            $response = $this->controller->check($request);
            
            $this->assertEquals(200, $response->getStatusCode());
        }
    }
}
