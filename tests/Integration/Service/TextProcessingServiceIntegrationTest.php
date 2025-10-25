<?php

declare(strict_types=1);

namespace RagSystem\Tests\Integration\Service;

use RagSystem\Tests\Integration\IntegrationTestCase;
use RagSystem\Application\Service\TextProcessingService;
use Psr\Log\LoggerInterface;
use Mockery;

/**
 * Интеграционные тесты для TextProcessingService
 * 
 * @covers \RagSystem\Application\Service\TextProcessingService
 * @covers \RagSystem\Infrastructure\DependencyInjection\Container
 */
class TextProcessingServiceIntegrationTest extends IntegrationTestCase
{
    private TextProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->getService(TextProcessingService::class);
    }

    /**
     * Тест извлечения текста из файла
     */
    public function testExtractTextFromFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($tempFile, 'Это тестовый текст для извлечения.');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile);
            $this->assertEquals('Это тестовый текст для извлечения.', $text);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста из несуществующего файла
     */
    public function testExtractTextFromNonExistentFile(): void
    {
        $this->expectException(\ErrorException::class);
        
        $this->service->extractTextFromFile('/path/to/nonexistent/file.txt');
    }

    /**
     * Тест разбиения текста на чанки
     */
    public function testChunkText(): void
    {
        $text = 'Это первый абзац. Это второй абзац. Это третий абзац.';
        
        $chunks = $this->service->chunkText($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    /**
     * Тест разбиения текста на чанки с пустым текстом
     */
    public function testChunkEmptyText(): void
    {
        $chunks = $this->service->chunkText('');
        
        $this->assertIsArray($chunks);
        $this->assertEmpty($chunks);
    }

    /**
     * Тест разбиения текста на чанки с очень коротким текстом
     */
    public function testChunkShortText(): void
    {
        $text = 'Короткий текст.';
        
        $chunks = $this->service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    /**
     * Тест разбиения текста на чанки с длинным текстом
     */
    public function testChunkLongText(): void
    {
        $text = str_repeat('Это предложение для тестирования. ', 10);
        
        $chunks = $this->service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    /**
     * Тест разбиения текста на чанки с различными разделителями
     */
    public function testChunkTextWithDifferentSeparators(): void
    {
        $text = "Первый абзац.\n\nВторой абзац.\n\nТретий абзац.";
        
        $chunks = $this->service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    /**
     * Тест разбиения текста на чанки с HTML тегами
     */
    public function testChunkTextWithHtmlTags(): void
    {
        $text = '<p>Первый абзац</p><p>Второй абзац</p><p>Третий абзац</p>';
        
        $chunks = $this->service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    /**
     * Тест разбиения текста на чанки с русским текстом
     */
    public function testChunkTextWithRussianText(): void
    {
        $text = 'Это русский текст для тестирования. Он содержит несколько предложений.';
        
        $chunks = $this->service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }

    /**
     * Тест разбиения текста на чанки с пунктуацией
     */
    public function testChunkTextWithPunctuation(): void
    {
        $text = 'Текст с пунктуацией! Вопрос? Восклицание! Еще один вопрос?';
        
        $chunks = $this->service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
    }
}
