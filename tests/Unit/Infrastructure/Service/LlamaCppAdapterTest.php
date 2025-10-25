<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;

/**
 * Тесты для LlamaCppAdapter
 * 
 * @covers \RagSystem\Infrastructure\Service\LlamaCppAdapter
 */
class LlamaCppAdapterTest extends BaseTestCase
{
    private LlamaCppAdapter $adapter;
    private Client $httpClient;
    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(Client::class);
        $this->logger = Mockery::mock(Logger::class);
        $this->logger->shouldReceive('pushHandler')->andReturnSelf();
        $this->logger->shouldReceive('info')->andReturnSelf();
        $this->logger->shouldReceive('warning')->andReturnSelf();
        $this->logger->shouldReceive('error')->andReturnSelf();
        
        $this->adapter = new LlamaCppAdapter(
            $this->httpClient,
            $this->logger,
            [
                'llm' => ['service_url' => 'http://localhost:8080'],
                'storage' => ['log_path' => '/tmp/test.log']
            ]
        );
    }

    /**
     * Тест успешной проверки загрузки модели
     */
    public function testEnsureModelLoadedSuccess(): void
    {
        $response = new Response(200, [], json_encode(['status' => 'ready']));

        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($response);

        $result = $this->adapter->ensureModelLoaded();

        $this->assertTrue($result);
    }

    /**
     * Тест неуспешной проверки загрузки модели
     */
    public function testEnsureModelLoadedFailure(): void
    {
        $response = new Response(500, [], json_encode(['status' => 'error']));

        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($response);

        $result = $this->adapter->ensureModelLoaded();

        $this->assertFalse($result);
    }

    /**
     * Тест создания адаптера с правильной конфигурацией
     */
    public function testAdapterCreation(): void
    {
        $this->assertInstanceOf(LlamaCppAdapter::class, $this->adapter);
    }

    /**
     * Тест работы с русским текстом
     */
    public function testRussianTextHandling(): void
    {
        $response = new Response(200, [], json_encode(['status' => 'ready']));

        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($response);

        $result = $this->adapter->ensureModelLoaded();

        $this->assertTrue($result);
    }

    /**
     * Тест генерации эмбеддинга
     */
    public function testGenerateEmbedding(): void
    {
        $text = 'Test text for embedding';
        $embeddingData = ['embedding' => array_fill(0, 1536, 0.1)];
        $response = new Response(200, [], json_encode($embeddingData));

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateEmbedding($text);

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    /**
     * Тест генерации простого эмбеддинга
     */
    public function testGenerateSimpleEmbedding(): void
    {
        $text = 'Test text for simple embedding';

        $result = $this->adapter->generateSimpleEmbedding($text);

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);

        foreach ($result as $value) {
            $this->assertGreaterThanOrEqual(-1, $value);
            $this->assertLessThanOrEqual(1, $value);
        }
    }

    /**
     * Тест оптимизации RAG промпта
     */
    public function testOptimizeRagPrompt(): void
    {
        $context = 'This is a test context';
        $question = 'What is this about?';

        $result = $this->adapter->optimizeRagPrompt($context, $question);

        $this->assertIsString($result);
        $this->assertStringContainsString($context, $result);
        $this->assertStringContainsString($question, $result);
    }

    /**
     * Тест генерации завершения
     */
    public function testGenerateCompletion(): void
    {
        $prompt = 'Test prompt for completion';
        $completionData = ['content' => 'Test completion response'];
        $response = new Response(200, [], json_encode($completionData));

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertEquals('Test completion response', $result);
    }

    /**
     * Тест обработки RAG промпта
     */
    public function testProcessRAGPrompt(): void
    {
        $prompt = 'Контекст: Test context\n\nВопрос: Test question';
        
        $result = $this->adapter->processRAGPrompt($prompt);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test context', $result);
        $this->assertStringContainsString('Test question', $result);
    }

    /**
     * Тест генерации эмбеддинга с ошибкой HTTP
     */
    public function testGenerateEmbeddingWithHttpError(): void
    {
        $text = 'Test text for embedding';
        $response = new Response(500, [], json_encode(['error' => 'Server error']));

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateEmbedding($text);

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    /**
     * Тест генерации эмбеддинга с исключением
     */
    public function testGenerateEmbeddingWithException(): void
    {
        $text = 'Test text for embedding';

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andThrow(new \GuzzleHttp\Exception\RequestException('Connection failed', new \GuzzleHttp\Psr7\Request('POST', 'test')));

        $result = $this->adapter->generateEmbedding($text);

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    /**
     * Тест генерации завершения с ошибкой модели
     */
    public function testGenerateCompletionWithModelError(): void
    {
        $prompt = 'Test prompt for completion';
        $response = new Response(500, [], json_encode(['error' => 'Model error']));

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertEquals('Произошла ошибка при генерации ответа.', $result);
    }

    /**
     * Тест генерации завершения с JSON ошибкой
     */
    public function testGenerateCompletionWithJsonError(): void
    {
        $prompt = 'Test prompt for completion';
        $response = new Response(200, [], 'invalid json');

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertStringContainsString('Failed to decode LLM response', $result);
    }

    /**
     * Тест генерации завершения с исключением
     */
    public function testGenerateCompletionWithException(): void
    {
        $prompt = 'Test prompt for completion';

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andThrow(new \GuzzleHttp\Exception\RequestException('Connection failed', new \GuzzleHttp\Psr7\Request('POST', 'test')));

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertStringContainsString('Произошла ошибка при обращении к модели', $result);
    }

    /**
     * Тест генерации завершения с недоступной моделью
     */
    public function testGenerateCompletionWithUnavailableModel(): void
    {
        $prompt = 'Test prompt for completion';

        $healthResponse = new Response(500, [], json_encode(['status' => 'error']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertEquals('Извините, модель временно недоступна. Попробуйте позже.', $result);
    }

    /**
     * Тест обработки RAG промпта на английском языке
     */
    public function testProcessRAGPromptEnglish(): void
    {
        $prompt = 'Context: Test context\n\nQuestion: Test question';
        
        $result = $this->adapter->processRAGPrompt($prompt);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test context', $result);
        $this->assertStringContainsString('Test question', $result);
        $this->assertStringContainsString('Answer in English', $result);
    }

    /**
     * Тест обработки RAG промпта без контекста
     */
    public function testProcessRAGPromptWithoutContext(): void
    {
        $prompt = 'Just a regular prompt without context';
        
        $result = $this->adapter->processRAGPrompt($prompt);

        $this->assertIsString($result);
        $this->assertEquals($prompt, $result);
    }

    /**
     * Тест обработки RAG промпта с русским форматом и ответом
     */
    public function testProcessRAGPromptWithAnswer(): void
    {
        $prompt = 'Контекст: Test context\n\nВопрос: Test question\n\nОтвет на русском языке:';
        
        $result = $this->adapter->processRAGPrompt($prompt);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test context', $result);
        $this->assertStringContainsString('Test question', $result);
        $this->assertStringContainsString('Ответ на русском языке:', $result);
    }

    /**
     * Тест обработки RAG промпта с английским форматом и ответом
     */
    public function testProcessRAGPromptEnglishWithAnswer(): void
    {
        $prompt = 'Context: Test context\n\nQuestion: Test question\n\nAnswer in English:';
        
        $result = $this->adapter->processRAGPrompt($prompt);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test context', $result);
        $this->assertStringContainsString('Test question', $result);
        $this->assertStringContainsString('Answer in English:', $result);
    }

    /**
     * Тест генерации эмбеддинга с кэшированием
     */
    public function testGenerateEmbeddingWithCache(): void
    {
        $text = 'Test text for caching';
        $embeddingData = ['embedding' => array_fill(0, 1536, 0.1)];
        $response = new Response(200, [], json_encode($embeddingData));

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andReturn($response);

        $result1 = $this->adapter->generateEmbedding($text);
        $this->assertIsArray($result1);
        $this->assertCount(1536, $result1);

        $result2 = $this->adapter->generateEmbedding($text);
        $this->assertIsArray($result2);
        $this->assertCount(1536, $result2);
        $this->assertEquals($result1, $result2);
    }

    /**
     * Тест генерации эмбеддинга с различными форматами ответа
     */
    public function testGenerateEmbeddingWithDifferentResponseFormats(): void
    {
        $text = 'Test text for different formats';

        $embeddingData = [['embedding' => array_fill(0, 1536, 0.1)]];
        $response = new Response(200, [], json_encode($embeddingData));

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateEmbedding($text);
        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    /**
     * Тест генерации эмбеддинга с обрезкой до 1536 измерений
     */
    public function testGenerateEmbeddingWithTruncation(): void
    {
        $text = 'Test text for truncation';
        $largeEmbedding = array_fill(0, 2000, 0.1);
        $embeddingData = ['embedding' => $largeEmbedding];
        $response = new Response(200, [], json_encode($embeddingData));

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateEmbedding($text);
        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    /**
     * Тест генерации эмбеддинга с дополнением нулями
     */
    public function testGenerateEmbeddingWithPadding(): void
    {
        $text = 'Test text for padding';
        $smallEmbedding = array_fill(0, 1000, 0.1);
        $embeddingData = ['embedding' => $smallEmbedding];
        $response = new Response(200, [], json_encode($embeddingData));

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/embedding', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateEmbedding($text);
        $this->assertIsArray($result);
        $this->assertCount(1536, $result);

        for ($i = 1000; $i < 1536; $i++) {
            $this->assertEquals(0.0, $result[$i]);
        }
    }

    /**
     * Тест генерации завершения с обработкой китайских символов
     */
    public function testGenerateCompletionWithChineseCharacters(): void
    {
        $prompt = 'Test prompt with 中文 characters';
        $completionData = ['content' => 'Response with 中文 characters and normal text'];
        $response = new Response(200, [], json_encode($completionData));

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('中文', $result);
        $this->assertStringContainsString('Response with', $result);
    }

    /**
     * Тест генерации завершения с обработкой служебных токенов
     */
    public function testGenerateCompletionWithServiceTokens(): void
    {
        $prompt = 'Test prompt';
        $completionData = ['content' => 'Response with <|user|> and <|assistant|> tokens'];
        $response = new Response(200, [], json_encode($completionData));

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('<|user|>', $result);
        $this->assertStringNotContainsString('<|assistant|>', $result);
        $this->assertStringContainsString('Response with', $result);
    }

    /**
     * Тест генерации завершения с обработкой множественных пробелов
     */
    public function testGenerateCompletionWithMultipleSpaces(): void
    {
        $prompt = 'Test prompt';
        $completionData = ['content' => 'Response   with    multiple     spaces'];
        $response = new Response(200, [], json_encode($completionData));

        $healthResponse = new Response(200, [], json_encode(['status' => 'ready']));
        $this->httpClient->shouldReceive('get')
            ->once()
            ->with('http://localhost:8080/health')
            ->andReturn($healthResponse);

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with('http://localhost:8080/completion', Mockery::type('array'))
            ->andReturn($response);

        $result = $this->adapter->generateCompletion($prompt);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('   ', $result);
        $this->assertStringContainsString('Response with multiple spaces', $result);
    }
}