<?php

declare(strict_types=1);

namespace RagSystem\Tests\Integration\Service;

use RagSystem\Tests\Integration\IntegrationTestCase;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;

/**
 * Интеграционные тесты для LlamaCppAdapter
 * 
 * @covers \RagSystem\Infrastructure\Service\LlamaCppAdapter
 * @covers \RagSystem\Infrastructure\DependencyInjection\Container
 */
class LlamaCppAdapterIntegrationTest extends IntegrationTestCase
{
    private LlamaCppAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = $this->getService(LlamaCppAdapter::class);
    }

    /**
     * Тест генерации простого эмбеддинга
     */
    public function testGenerateSimpleEmbedding(): void
    {
        $text = 'Тестовый текст для эмбеддинга';

        $embedding = $this->adapter->generateSimpleEmbedding($text);

        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);

        foreach ($embedding as $value) {
            $this->assertIsFloat($value);
        }
    }

    /**
     * Тест генерации эмбеддинга для пустого текста
     */
    public function testGenerateSimpleEmbeddingEmptyText(): void
    {
        $embedding = $this->adapter->generateSimpleEmbedding('');
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для длинного текста
     */
    public function testGenerateSimpleEmbeddingLongText(): void
    {
        $text = str_repeat('Это очень длинный текст для тестирования эмбеддинга. ', 100);
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для текста с русскими символами
     */
    public function testGenerateSimpleEmbeddingRussianText(): void
    {
        $text = 'Это русский текст с кириллицей для тестирования эмбеддинга';
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для текста с специальными символами
     */
    public function testGenerateSimpleEmbeddingSpecialCharacters(): void
    {
        $text = 'Text with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для текста с числами
     */
    public function testGenerateSimpleEmbeddingNumbers(): void
    {
        $text = 'Text with numbers: 1234567890 and 3.14159';
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для текста с переносами строк
     */
    public function testGenerateSimpleEmbeddingMultilineText(): void
    {
        $text = "Line 1\nLine 2\nLine 3";
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для текста с HTML тегами
     */
    public function testGenerateSimpleEmbeddingHtmlText(): void
    {
        $text = '<p>HTML text with <strong>tags</strong> and <em>formatting</em></p>';
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест генерации эмбеддинга для текста с JSON
     */
    public function testGenerateSimpleEmbeddingJsonText(): void
    {
        $text = '{"name": "test", "value": 123, "active": true}';
        
        $embedding = $this->adapter->generateSimpleEmbedding($text);
        
        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
        $this->assertCount(1536, $embedding);
    }

    /**
     * Тест оптимизации RAG промпта
     */
    public function testOptimizeRagPrompt(): void
    {
        $context = 'Это контекст для RAG системы';
        $question = 'Какой вопрос задает пользователь?';
        
        $optimizedPrompt = $this->adapter->optimizeRagPrompt($context, $question);
        
        $this->assertIsString($optimizedPrompt);
        $this->assertStringContainsString($context, $optimizedPrompt);
        $this->assertStringContainsString($question, $optimizedPrompt);
    }

    /**
     * Тест обработки RAG промпта
     */
    public function testProcessRAGPrompt(): void
    {
        $prompt = 'Ответь на вопрос: Что такое RAG?';
        
        $processedPrompt = $this->adapter->processRAGPrompt($prompt);
        
        $this->assertIsString($processedPrompt);
        $this->assertStringContainsString('RAG', $processedPrompt);
    }
}