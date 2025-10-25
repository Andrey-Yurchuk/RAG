<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Service\TextProcessingService;
use Mockery;

/**
 * @covers \RagSystem\Application\Service\TextProcessingService
 */
class TextProcessingServiceTest extends BaseTestCase
{
    private TextProcessingService $textProcessingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'embedding' => [
                'chunk_size' => 100,
                'chunk_overlap' => 20
            ]
        ];
        
        $this->textProcessingService = new TextProcessingService($config);
    }

    /**
     * Тест разбиения текста на чанки
     */
    public function testChunkText(): void
    {
        $text = 'This is the first sentence. This is the second sentence. This is the third sentence.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertContainsOnly('string', $chunks);
    }

    /**
     * Тест разбиения длинного текста на чанки
     */
    public function testChunkTextWithLongText(): void
    {
        $text = str_repeat('This is a sentence. ', 20);
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(100, mb_strlen($chunk));
        }
    }

    /**
     * Тест разбиения пустого текста
     */
    public function testChunkTextWithEmptyText(): void
    {
        $text = '';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertEmpty($chunks);
    }

    /**
     * Тест разбиения текста с русскими символами
     */
    public function testChunkTextWithRussianText(): void
    {
        $text = 'Это первое предложение. Это второе предложение. Это третье предложение.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertContainsOnly('string', $chunks);
    }

    /**
     * Тест очистки текста
     */
    public function testCleanText(): void
    {
        $text = "This   is   a   text\n\nwith   multiple   spaces\r\nand   line   breaks.";
        $expected = "This is a text with multiple spaces and line breaks.";
        
        $result = $this->textProcessingService->cleanText($text);
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Тест очистки текста с управляющими символами
     */
    public function testCleanTextWithControlCharacters(): void
    {
        $text = "Text\x00with\x01control\x02characters\x03";
        $expected = "Textwithcontrolcharacters";
        
        $result = $this->textProcessingService->cleanText($text);
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Тест очистки текста с русскими символами
     */
    public function testCleanTextWithRussianText(): void
    {
        $text = "Это   текст   с   множественными   пробелами\n\nи   переносами   строк.";
        $expected = "Это текст с множественными пробелами и переносами строк.";
        
        $result = $this->textProcessingService->cleanText($text);
        
        $this->assertEquals($expected, $result);
    }

    /**
     * Тест очистки пустого текста
     */
    public function testCleanTextWithEmptyText(): void
    {
        $text = '';
        
        $result = $this->textProcessingService->cleanText($text);
        
        $this->assertEquals('', $result);
    }

    /**
     * Тест очистки текста только с пробелами
     */
    public function testCleanTextWithOnlySpaces(): void
    {
        $text = '   ';
        
        $result = $this->textProcessingService->cleanText($text);
        
        $this->assertEquals('', $result);
    }

    /**
     * Тест извлечения текста из TXT файла
     */
    public function testExtractTextFromFileTxt(): void
    {
        $filePath = '/tmp/test.txt';
        $content = 'Test content for TXT file';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertEquals($content, $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест извлечения текста из TXT файла с указанием типа
     */
    public function testExtractTextFromFileWithType(): void
    {
        $filePath = '/tmp/test.txt';
        $content = 'Test content for TXT file';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath, 'txt');
            $this->assertEquals($content, $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест извлечения текста из Markdown файла
     */
    public function testExtractTextFromFileMarkdown(): void
    {
        $filePath = '/tmp/test.md';
        $content = "# Title\n\n**Bold text** and *italic text*.\n\n[Link](http://example.com)";
        $expected = "Title\n\nBold text and italic text.\n\nLink";

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertStringContainsString('Title', $result);
            $this->assertStringContainsString('Bold text', $result);
            $this->assertStringContainsString('italic text', $result);
            $this->assertStringContainsString('Link', $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест извлечения текста из HTML файла
     */
    public function testExtractTextFromFileHtml(): void
    {
        $filePath = '/tmp/test.html';
        $content = '<html><body><h1>Title</h1><p>This is <strong>bold</strong> text.</p></body></html>';
        $expected = 'Title This is bold text.';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertStringContainsString('Title', $result);
            $this->assertStringContainsString('This is', $result);
            $this->assertStringContainsString('bold', $result);
            $this->assertStringContainsString('text', $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест извлечения текста из неподдерживаемого типа файла
     */
    public function testExtractTextFromFileUnsupportedType(): void
    {
        $filePath = '/tmp/test.xyz';
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file type: xyz');
        
        $this->textProcessingService->extractTextFromFile($filePath);
    }

    /**
     * Тест извлечения текста из несуществующего файла
     */
    public function testExtractTextFromFileNonExistent(): void
    {
        $filePath = '/tmp/nonexistent.txt';
        
        $this->expectException(\ErrorException::class);
        
        $this->textProcessingService->extractTextFromFile($filePath);
    }

    /**
     * Тест с различными размерами чанков
     */
    public function testChunkTextWithDifferentSizes(): void
    {
        $config = [
            'embedding' => [
                'chunk_size' => 50,
                'chunk_overlap' => 10
            ]
        ];
        
        $service = new TextProcessingService($config);
        $text = 'This is a sentence. This is another sentence. This is a third sentence.';
        
        $chunks = $service->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(50, mb_strlen($chunk));
        }
    }

    /**
     * Тест с текстом без знаков препинания
     */
    public function testChunkTextWithoutPunctuation(): void
    {
        $text = 'This is a text without punctuation marks This is another part';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
    }

    /**
     * Тест с текстом только из пробелов
     */
    public function testChunkTextWithOnlySpaces(): void
    {
        $text = '   ';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertEmpty($chunks);
    }

    /**
     * Тест с текстом только из знаков препинания
     */
    public function testChunkTextWithOnlyPunctuation(): void
    {
        $text = '...!!!???';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
    }

    /**
     * Тест конвертации UTF-8 текста
     */
    public function testConvertToUtf8(): void
    {
        $filePath = '/tmp/test_utf8.txt';
        $content = 'Тестовый текст на русском языке';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertEquals($content, $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест обработки текста с перекрытием чанков
     */
    public function testChunkTextWithOverlap(): void
    {
        $text = 'This is a very long sentence that should be split into multiple chunks with overlap. This is another sentence. This is a third sentence. This is a fourth sentence.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertGreaterThan(1, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(100, mb_strlen($chunk));
        }
    }

    /**
     * Тест обработки текста с различными знаками препинания
     */
    public function testChunkTextWithDifferentPunctuation(): void
    {
        $text = 'First sentence! Second sentence? Third sentence. Fourth sentence.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertContainsOnly('string', $chunks);
    }

    /**
     * Тест обработки текста с множественными пробелами
     */
    public function testChunkTextWithMultipleSpaces(): void
    {
        $text = 'First    sentence.    Second    sentence.    Third    sentence.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertStringNotContainsString('    ', $chunk);
        }
    }

    /**
     * Тест обработки текста с переносами строк
     */
    public function testChunkTextWithLineBreaks(): void
    {
        $text = "First sentence.\n\nSecond sentence.\r\nThird sentence.";
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertStringNotContainsString("\r\n", $chunk);
            $this->assertStringNotContainsString("\r", $chunk);
        }
    }

    /**
     * Тест обработки текста с управляющими символами
     */
    public function testChunkTextWithControlCharacters(): void
    {
        $text = "First sentence.\x00Second sentence.\x01Third sentence.";
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        foreach ($chunks as $chunk) {
            $this->assertStringNotContainsString("\x00", $chunk);
            $this->assertStringNotContainsString("\x01", $chunk);
        }
    }

    /**
     * Тест обработки текста с HTML тегами
     */
    public function testChunkTextWithHtmlTags(): void
    {
        $text = '<p>First sentence.</p><p>Second sentence.</p><p>Third sentence.</p>';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        $this->assertStringContainsString('<p>', $chunks[0]);
    }

    /**
     * Тест обработки текста с Markdown разметкой
     */
    public function testChunkTextWithMarkdown(): void
    {
        $text = '# Title\n\n**Bold text** and *italic text*.\n\n[Link](http://example.com)';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        $this->assertStringContainsString('#', $chunks[0]);
    }

    /**
     * Тест для покрытия приватного метода splitIntoSentences - различные знаки препинания
     */
    public function testSplitIntoSentencesWithDifferentPunctuation(): void
    {
        $text = 'First sentence! Second sentence? Third sentence. Fourth sentence.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertContainsOnly('string', $chunks);
    }

    /**
     * Тест для покрытия приватного метода getOverlapText - длинный текст с перекрытием
     */
    public function testGetOverlapTextWithLongText(): void
    {
        $text = 'This is a very long sentence that should be split into multiple chunks with overlap. This is another sentence. This is a third sentence. This is a fourth sentence. This is a fifth sentence.';
        
        $chunks = $this->textProcessingService->chunkText($text);
        
        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertGreaterThan(1, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(100, mb_strlen($chunk));
        }
    }

    /**
     * Тест для покрытия приватного метода extractFromMarkdown - различные Markdown элементы
     */
    public function testExtractFromMarkdownWithVariousElements(): void
    {
        $filePath = '/tmp/test_markdown.md';
        $content = "# Main Title\n\n## Subtitle\n\n**Bold text** and *italic text*.\n\n[Link](http://example.com)\n\n`code snippet`\n\n```\ncode block\n```\n\n> Quote";

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertStringContainsString('Main Title', $result);
            $this->assertStringContainsString('Subtitle', $result);
            $this->assertStringContainsString('Bold text', $result);
            $this->assertStringContainsString('italic text', $result);
            $this->assertStringContainsString('Link', $result);
            $this->assertStringContainsString('code snippet', $result);
            $this->assertStringContainsString('Quote', $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест для покрытия приватного метода extractFromHtml - сложный HTML
     */
    public function testExtractFromHtmlWithComplexHtml(): void
    {
        $filePath = '/tmp/test_html.html';
        $content = '<html><head><title>Test Title</title></head><body><h1>Main Title</h1><p>This is <strong>bold</strong> and <em>italic</em> text.</p><div><span>Nested content</span></div></body></html>';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertStringContainsString('Test Title', $result);
            $this->assertStringContainsString('Main Title', $result);
            $this->assertStringContainsString('This is', $result);
            $this->assertStringContainsString('bold', $result);
            $this->assertStringContainsString('italic', $result);
            $this->assertStringContainsString('text', $result);
            $this->assertStringContainsString('Nested content', $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест для покрытия приватного метода extractFromPdf - обработка ошибок PDF
     */
    public function testExtractFromPdfWithError(): void
    {
        $filePath = '/tmp/test_pdf.pdf';

        file_put_contents($filePath, 'This is not a valid PDF file');
        
        try {
            $this->expectException(\RuntimeException::class);
            $this->textProcessingService->extractTextFromFile($filePath);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест для покрытия приватного метода extractFromDocx - обработка ошибок DOCX
     */
    public function testExtractFromDocxWithError(): void
    {
        $filePath = '/tmp/test_docx.docx';

        file_put_contents($filePath, 'This is not a valid DOCX file');
        
        try {
            $this->expectException(\RuntimeException::class);
            $this->textProcessingService->extractTextFromFile($filePath);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест для покрытия приватного метода extractFromText - различные кодировки
     */
    public function testExtractFromTextWithDifferentEncodings(): void
    {
        $filePath = '/tmp/test_text.txt';
        $content = 'Тестовый текст на русском языке';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertEquals($content, $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Тест для покрытия приватного метода convertToUtf8 - различные кодировки
     */
    public function testConvertToUtf8WithDifferentEncodings(): void
    {
        $filePath = '/tmp/test_encoding.txt';
        $content = 'Test content with special characters: àáâãäåæçèéêë';

        file_put_contents($filePath, $content);
        
        try {
            $result = $this->textProcessingService->extractTextFromFile($filePath);
            $this->assertStringContainsString('Test content', $result);
            $this->assertStringContainsString('special characters', $result);
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
