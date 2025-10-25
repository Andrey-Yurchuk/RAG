<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use Exception;
use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use Mockery;

/**
 * @covers \RagSystem\Application\Service\EmbeddingService
 */
class EmbeddingServiceTest extends BaseTestCase
{
    private EmbeddingService $embeddingService;
    private LlamaCppAdapter $llamaCppAdapter;
    private \Psr\Log\LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->llamaCppAdapter = Mockery::mock(LlamaCppAdapter::class);
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $this->embeddingService = new EmbeddingService($this->llamaCppAdapter, $this->logger);
    }

    /**
     * Тест генерации эмбеддинга для одного текста
     */
    public function testGenerateEmbedding(): void
    {
        $text = 'This is a test text for embedding generation';
        $expectedEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with($text)
            ->andReturn($expectedEmbedding)
            ->once();

        $result = $this->embeddingService->generateEmbedding($text);

        $this->assertEquals($expectedEmbedding, $result);
    }

    /**
     * Тест генерации эмбеддингов для массива текстов
     */
    public function testGenerateEmbeddings(): void
    {
        $texts = [
            'First text for embedding',
            'Second text for embedding',
            'Third text for embedding'
        ];
        $expectedEmbeddings = [
            [0.1, 0.2, 0.3],
            [0.4, 0.5, 0.6],
            [0.7, 0.8, 0.9]
        ];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('First text for embedding')
            ->andReturn($expectedEmbeddings[0])
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('Second text for embedding')
            ->andReturn($expectedEmbeddings[1])
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('Third text for embedding')
            ->andReturn($expectedEmbeddings[2])
            ->once();

        $result = $this->embeddingService->generateEmbeddings($texts);

        $this->assertEquals($expectedEmbeddings, $result);
    }

    /**
     * Тест генерации эмбеддингов с ошибкой в одном из текстов
     */
    public function testGenerateEmbeddingsWithPartialError(): void
    {
        $texts = [
            'Valid text',
            'Invalid text that causes error',
            'Another valid text'
        ];
        $expectedEmbeddings = [
            [0.1, 0.2, 0.3],
            null,
            [0.7, 0.8, 0.9]
        ];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('Valid text')
            ->andReturn($expectedEmbeddings[0])
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('Invalid text that causes error')
            ->andThrow(new Exception('Embedding generation failed'))
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('Another valid text')
            ->andReturn($expectedEmbeddings[2])
            ->once();

        $this->logger->shouldReceive('error')
            ->with('Failed to generate embedding in batch', Mockery::type('array'))
            ->once();

        $result = $this->embeddingService->generateEmbeddings($texts);

        $this->assertEquals($expectedEmbeddings, $result);
    }

    /**
     * Тест обработки ошибки при генерации эмбеддинга
     */
    public function testGenerateEmbeddingWithException(): void
    {
        $text = 'Text that causes error';
        $exception = new Exception('Embedding service unavailable');

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with($text)
            ->andThrow($exception)
            ->once();

        $this->logger->shouldReceive('error')
            ->with('Failed to generate embedding', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate embedding: Embedding service unavailable');

        $this->embeddingService->generateEmbedding($text);
    }

    /**
     * Тест проверки здоровья сервиса
     */
    public function testCheckHealth(): void
    {
        $this->llamaCppAdapter->shouldReceive('ensureModelLoaded')
            ->andReturn(true)
            ->once();

        $result = $this->embeddingService->checkHealth();

        $this->assertTrue($result);
    }

    /**
     * Тест проверки здоровья сервиса (не готов)
     */
    public function testCheckHealthNotReady(): void
    {
        $this->llamaCppAdapter->shouldReceive('ensureModelLoaded')
            ->andReturn(false)
            ->once();

        $result = $this->embeddingService->checkHealth();

        $this->assertFalse($result);
    }

    /**
     * Тест с русским текстом
     */
    public function testGenerateEmbeddingWithRussianText(): void
    {
        $text = 'Это тестовый текст на русском языке для генерации эмбеддинга';
        $expectedEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with($text)
            ->andReturn($expectedEmbedding)
            ->once();

        $result = $this->embeddingService->generateEmbedding($text);

        $this->assertEquals($expectedEmbedding, $result);
    }

    /**
     * Тест с пустым массивом текстов
     */
    public function testGenerateEmbeddingsWithEmptyArray(): void
    {
        $texts = [];
        $expectedEmbeddings = [];

        $result = $this->embeddingService->generateEmbeddings($texts);

        $this->assertEquals($expectedEmbeddings, $result);
    }

    /**
     * Тест с одним текстом в массиве
     */
    public function testGenerateEmbeddingsWithSingleText(): void
    {
        $texts = ['Single text'];
        $expectedEmbeddings = [[0.1, 0.2, 0.3]];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with('Single text')
            ->andReturn($expectedEmbeddings[0])
            ->once();

        $result = $this->embeddingService->generateEmbeddings($texts);

        $this->assertEquals($expectedEmbeddings, $result);
    }

    /**
     * Тест с длинным текстом
     */
    public function testGenerateEmbeddingWithLongText(): void
    {
        $text = str_repeat('This is a very long text. ', 100);
        $expectedEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with($text)
            ->andReturn($expectedEmbedding)
            ->once();

        $result = $this->embeddingService->generateEmbedding($text);

        $this->assertEquals($expectedEmbedding, $result);
    }

    /**
     * Тест с пустой строкой
     */
    public function testGenerateEmbeddingWithEmptyString(): void
    {
        $text = '';
        $expectedEmbedding = [0.0, 0.0, 0.0];

        $this->llamaCppAdapter->shouldReceive('generateEmbedding')
            ->with($text)
            ->andReturn($expectedEmbedding)
            ->once();

        $result = $this->embeddingService->generateEmbedding($text);

        $this->assertEquals($expectedEmbedding, $result);
    }
}
