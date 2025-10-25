<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use Exception;
use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Service\LLMService;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use Mockery;

/**
 * @covers \RagSystem\Application\Service\LLMService
 */
class LLMServiceTest extends BaseTestCase
{
    private LLMService $llmService;
    private LlamaCppAdapter $llamaCppAdapter;
    private \Psr\Log\LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->llamaCppAdapter = Mockery::mock(LlamaCppAdapter::class);
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        
        $this->llmService = new LLMService($this->llamaCppAdapter, $this->logger);
    }

    /**
     * Тест генерации ответа с простым промптом
     */
    public function testGenerateResponse(): void
    {
        $prompt = 'What is the meaning of life?';
        $expectedResponse = 'The meaning of life is to find purpose and happiness.';

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with(Mockery::type('string'))
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')
            ->with('Generated LLM response', Mockery::type('array'))
            ->once();

        $result = $this->llmService->generateResponse($prompt);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации ответа с контекстом
     */
    public function testGenerateResponseWithContext(): void
    {
        $prompt = 'What is AI?';
        $context = ['AI stands for Artificial Intelligence', 'AI is used in many applications'];
        $expectedResponse = 'AI stands for Artificial Intelligence and is used in many applications.';

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with(Mockery::type('string'))
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')
            ->with('Generated LLM response', Mockery::type('array'))
            ->once();

        $result = $this->llmService->generateResponse($prompt, $context);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа
     */
    public function testGenerateRAGResponse(): void
    {
        $question = 'What is machine learning?';
        $relevantChunks = [
            ['chunk_text' => 'Machine learning is a subset of AI'],
            ['chunk_text' => 'It involves training algorithms on data']
        ];
        $expectedResponse = 'Machine learning is a subset of AI that involves training algorithms on data.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')
            ->with('Generated RAG prompt', Mockery::type('array'))
            ->once();

        $this->logger->shouldReceive('debug')
            ->with('Generated RAG response', Mockery::type('array'))
            ->once();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с русским вопросом
     */
    public function testGenerateRAGResponseWithRussianQuestion(): void
    {
        $question = 'Что такое машинное обучение?';
        $relevantChunks = [
            ['chunk_text' => 'Машинное обучение - это подмножество ИИ'],
            ['chunk_text' => 'Оно включает обучение алгоритмов на данных']
        ];
        $expectedResponse = 'Машинное обучение - это подмножество ИИ, которое включает обучение алгоритмов на данных.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест обработки ошибки при генерации ответа
     */
    public function testGenerateResponseWithException(): void
    {
        $prompt = 'Test prompt';
        $exception = new Exception('LLM service unavailable');

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->andThrow($exception)
            ->once();

        $this->logger->shouldReceive('error')
            ->with('Failed to generate LLM response', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate response: LLM service unavailable');

        $this->llmService->generateResponse($prompt);
    }

    /**
     * Тест обработки ошибки при генерации RAG ответа
     */
    public function testGenerateRAGResponseWithException(): void
    {
        $question = 'Test question';
        $relevantChunks = [['chunk_text' => 'Test chunk']];
        $exception = new Exception('RAG service unavailable');

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->andThrow($exception)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();
        $this->logger->shouldReceive('error')
            ->with('Failed to generate RAG response', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate response: RAG service unavailable');

        $this->llmService->generateRAGResponse($question, $relevantChunks);
    }

    /**
     * Тест проверки здоровья сервиса
     */
    public function testCheckHealth(): void
    {
        $this->llamaCppAdapter->shouldReceive('ensureModelLoaded')
            ->andReturn(true)
            ->once();

        $result = $this->llmService->checkHealth();

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

        $result = $this->llmService->checkHealth();

        $this->assertFalse($result);
    }

    /**
     * Тест с пустым контекстом
     */
    public function testGenerateResponseWithEmptyContext(): void
    {
        $prompt = 'Simple question';
        $context = [];
        $expectedResponse = 'Simple answer';

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with(Mockery::type('string'))
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateResponse($prompt, $context);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест с различными типами чанков
     */
    public function testGenerateRAGResponseWithDifferentChunkTypes(): void
    {
        $question = 'Test question';
        $relevantChunks = [
            ['chunk_text' => 'Array chunk'],
            'String chunk',
            ['other_field' => 'value']
        ];
        $expectedResponse = 'Test response';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест с английским вопросом
     */
    public function testGenerateRAGResponseWithEnglishQuestion(): void
    {
        $question = 'What is artificial intelligence?';
        $relevantChunks = [['chunk_text' => 'AI is a technology']];
        $expectedResponse = 'AI is a technology that simulates human intelligence.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации ответа с большим контекстом
     */
    public function testGenerateResponseWithLargeContext(): void
    {
        $prompt = 'What is AI?';
        $context = [
            'AI stands for Artificial Intelligence',
            'AI is used in many applications',
            'Machine learning is a subset of AI',
            'Deep learning is a subset of machine learning',
            'AI can be used for automation and decision making'
        ];
        $expectedResponse = 'AI stands for Artificial Intelligence and is used in many applications.';

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with(Mockery::type('string'))
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateResponse($prompt, $context);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с большим количеством чанков
     */
    public function testGenerateRAGResponseWithManyChunks(): void
    {
        $question = 'What is machine learning?';
        $relevantChunks = [
            ['chunk_text' => 'Machine learning is a subset of AI'],
            ['chunk_text' => 'It involves training algorithms on data'],
            ['chunk_text' => 'Supervised learning uses labeled data'],
            ['chunk_text' => 'Unsupervised learning finds patterns in data'],
            ['chunk_text' => 'Reinforcement learning learns from rewards']
        ];
        $expectedResponse = 'Machine learning is a subset of AI that involves training algorithms on data.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с пустыми чанками
     */
    public function testGenerateRAGResponseWithEmptyChunks(): void
    {
        $question = 'What is AI?';
        $relevantChunks = [];
        $expectedResponse = 'I cannot answer this question based on the provided context.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с чанками без chunk_text
     */
    public function testGenerateRAGResponseWithChunksWithoutText(): void
    {
        $question = 'What is AI?';
        $relevantChunks = [
            ['other_field' => 'value1'],
            ['another_field' => 'value2'],
            ['chunk_text' => 'AI is artificial intelligence']
        ];
        $expectedResponse = 'AI is artificial intelligence.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с чанками разных типов
     */
    public function testGenerateRAGResponseWithMixedChunkTypes(): void
    {
        $question = 'What is AI?';
        $relevantChunks = [
            ['chunk_text' => 'AI is artificial intelligence'],
            'String chunk without array',
            ['chunk_text' => 'AI can be used for automation'],
            ['other_field' => 'value'],
            ['chunk_text' => 'Machine learning is a subset of AI']
        ];
        $expectedResponse = 'AI is artificial intelligence that can be used for automation.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации ответа с длинным промптом
     */
    public function testGenerateResponseWithLongPrompt(): void
    {
        $prompt = str_repeat('This is a very long prompt. ', 100);
        $expectedResponse = 'This is a response to a long prompt.';

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with(Mockery::type('string'))
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateResponse($prompt);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с длинным вопросом
     */
    public function testGenerateRAGResponseWithLongQuestion(): void
    {
        $question = str_repeat('This is a very long question about artificial intelligence. ', 50);
        $relevantChunks = [['chunk_text' => 'AI is artificial intelligence']];
        $expectedResponse = 'AI is artificial intelligence.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации ответа с русским контекстом
     */
    public function testGenerateResponseWithRussianContext(): void
    {
        $prompt = 'Что такое ИИ?';
        $context = [
            'ИИ означает искусственный интеллект',
            'ИИ используется во многих приложениях',
            'Машинное обучение - это подмножество ИИ'
        ];
        $expectedResponse = 'ИИ означает искусственный интеллект и используется во многих приложениях.';

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with(Mockery::type('string'))
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateResponse($prompt, $context);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Тест генерации RAG ответа с русскими чанками
     */
    public function testGenerateRAGResponseWithRussianChunks(): void
    {
        $question = 'Что такое машинное обучение?';
        $relevantChunks = [
            ['chunk_text' => 'Машинное обучение - это подмножество ИИ'],
            ['chunk_text' => 'Оно включает обучение алгоритмов на данных'],
            ['chunk_text' => 'Существует обучение с учителем и без учителя']
        ];
        $expectedResponse = 'Машинное обучение - это подмножество ИИ, которое включает обучение алгоритмов на данных.';

        $this->llamaCppAdapter->shouldReceive('processRAGPrompt')
            ->with(Mockery::type('string'))
            ->andReturn('processed_prompt')
            ->once();

        $this->llamaCppAdapter->shouldReceive('generateCompletion')
            ->with('processed_prompt')
            ->andReturn($expectedResponse)
            ->once();

        $this->logger->shouldReceive('debug')->andReturnSelf();

        $result = $this->llmService->generateRAGResponse($question, $relevantChunks);

        $this->assertEquals($expectedResponse, $result);
    }
}
