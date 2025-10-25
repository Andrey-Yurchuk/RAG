<?php

declare(strict_types=1);

namespace RagSystem\Tests\Integration\Service;

use ErrorException;
use Exception;
use RagSystem\Tests\Integration\IntegrationTestCase;
use RagSystem\Application\Service\TextProcessingService;
use Psr\Log\LoggerInterface;
use Mockery;

/**
 * Интеграционные тесты для TextProcessingService (vol.2.0)
 * 
 * @covers \RagSystem\Application\Service\TextProcessingService
 * @covers \RagSystem\Infrastructure\DependencyInjection\Container
 */
class TextProcessingServiceExtendedIntegrationTest extends IntegrationTestCase
{
    private TextProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->getService(TextProcessingService::class);
    }

    /**
     * Тест очистки текста
     */
    public function testCleanText(): void
    {
        $dirtyText = "  Это   текст   с   лишними   пробелами  \n\n\t\t";
        $cleanText = $this->service->cleanText($dirtyText);
        
        $this->assertEquals("Это текст с лишними пробелами", $cleanText);
    }

    /**
     * Тест очистки текста с HTML тегами
     */
    public function testCleanTextWithHtmlTags(): void
    {
        $htmlText = "<p>Это <strong>текст</strong> с <em>HTML</em> тегами</p>";
        $cleanText = $this->service->cleanText($htmlText);

        $this->assertEquals("<p>Это <strong>текст</strong> с <em>HTML</em> тегами</p>", $cleanText);
    }

    /**
     * Тест очистки текста с множественными пробелами
     */
    public function testCleanTextWithMultipleSpaces(): void
    {
        $textWithSpaces = "Текст    с    множественными    пробелами";
        $cleanText = $this->service->cleanText($textWithSpaces);
        
        $this->assertEquals("Текст с множественными пробелами", $cleanText);
    }

    /**
     * Тест очистки текста с переносами строк
     */
    public function testCleanTextWithNewlines(): void
    {
        $textWithNewlines = "Текст\n\nс\n\nпереносами\n\nстрок";
        $cleanText = $this->service->cleanText($textWithNewlines);
        
        $this->assertEquals("Текст с переносами строк", $cleanText);
    }

    /**
     * Тест очистки текста с табуляцией
     */
    public function testCleanTextWithTabs(): void
    {
        $textWithTabs = "Текст\t\tс\t\tтабуляцией";
        $cleanText = $this->service->cleanText($textWithTabs);
        
        $this->assertEquals("Текст с табуляцией", $cleanText);
    }

    /**
     * Тест очистки пустого текста
     */
    public function testCleanEmptyText(): void
    {
        $cleanText = $this->service->cleanText('');
        
        $this->assertEquals('', $cleanText);
    }

    /**
     * Тест очистки текста только с пробелами
     */
    public function testCleanTextOnlySpaces(): void
    {
        $cleanText = $this->service->cleanText('   ');
        
        $this->assertEquals('', $cleanText);
    }

    /**
     * Тест очистки текста с русскими символами
     */
    public function testCleanTextWithRussianCharacters(): void
    {
        $russianText = "  Это   русский   текст   с   кириллицей  ";
        $cleanText = $this->service->cleanText($russianText);
        
        $this->assertEquals("Это русский текст с кириллицей", $cleanText);
    }

    /**
     * Тест очистки текста с специальными символами
     */
    public function testCleanTextWithSpecialCharacters(): void
    {
        $specialText = "  Текст   с   символами:   !@#$%^&*()  ";
        $cleanText = $this->service->cleanText($specialText);
        
        $this->assertEquals("Текст с символами: !@#$%^&*()", $cleanText);
    }

    /**
     * Тест извлечения текста из PDF файла
     */
    public function testExtractTextFromPdfFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 fake pdf content');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile, 'pdf');
            $this->assertIsString($text);
        } catch (Exception $e) {
            $this->assertStringContainsString('PDF', $e->getMessage());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста из DOCX файла
     */
    public function testExtractTextFromDocxFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.docx';
        file_put_contents($tempFile, 'fake docx content');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile, 'docx');
            $this->assertIsString($text);
        } catch (Exception $e) {
            $this->assertStringContainsString('DOCX', $e->getMessage());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста из HTML файла
     */
    public function testExtractTextFromHtmlFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.html';
        file_put_contents($tempFile, '<html><body><p>HTML content</p></body></html>');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile, 'html');
            
            $this->assertIsString($text);
            $this->assertStringContainsString('HTML content', $text);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста из MD файла
     */
    public function testExtractTextFromMarkdownFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.md';
        file_put_contents($tempFile, '# Markdown Title\n\nThis is **markdown** content.');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile, 'md');
            
            $this->assertIsString($text);
            $this->assertStringContainsString('Markdown Title', $text);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста с автоматическим определением типа
     */
    public function testExtractTextWithAutoDetection(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($tempFile, 'Auto-detected text content');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile);
            
            $this->assertIsString($text);
            $this->assertEquals('Auto-detected text content', $text);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста с неподдерживаемым типом файла
     */
    public function testExtractTextFromUnsupportedFileType(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.xyz';
        file_put_contents($tempFile, 'Unsupported file content');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile, 'xyz');
            $this->fail('Expected exception for unsupported file type');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Unsupported file type', $e->getMessage());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста с несуществующим файлом
     */
    public function testExtractTextFromNonExistentFile(): void
    {
        $this->expectException(ErrorException::class);
        
        $this->service->extractTextFromFile('/path/to/nonexistent/file.txt');
    }

    /**
     * Тест извлечения текста с пустым файлом
     */
    public function testExtractTextFromEmptyFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($tempFile, '');
        
        try {
            $text = $this->service->extractTextFromFile($tempFile);
            
            $this->assertIsString($text);
            $this->assertEquals('', $text);
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Тест извлечения текста с большим файлом
     */
    public function testExtractTextFromLargeFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        $largeContent = str_repeat('Large file content. ', 1000);
        file_put_contents($tempFile, $largeContent);
        
        try {
            $text = $this->service->extractTextFromFile($tempFile);
            
            $this->assertIsString($text);
            $this->assertStringContainsString('Large file content', $text);
        } finally {
            unlink($tempFile);
        }
    }
}
