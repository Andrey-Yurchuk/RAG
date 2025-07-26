<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use InvalidArgumentException;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class TextProcessingService
{
    private int $chunkSize;
    private int $chunkOverlap;

    public function __construct(array $config)
    {
        $this->chunkSize = $config['embedding']['chunk_size'];
        $this->chunkOverlap = $config['embedding']['chunk_overlap'];
    }

    /**
     * Разбивает текст на чанки заданного размера, соседние чанки содержат общую часть
     * текста (перекрытие) для сохранения контекста
     */
    public function chunkText(string $text): array
    {
        $text = $this->cleanText($text);
        $sentences = $this->splitIntoSentences($text);

        if (empty($sentences)) {
            return [];
        }

        $chunks = [];
        $currentChunk = '';
        $currentLength = 0;

        foreach ($sentences as $sentence) {
            $sentenceLength = mb_strlen($sentence);

            // Если добавление этого предложения превысит размер чанка, завершаем текущий чанк
            if ($currentLength + $sentenceLength > $this->chunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);

                // Начинаем новый чанк с перекрытием
                $currentChunk = $this->getOverlapText($currentChunk) . ' ' . $sentence;
                $currentLength = mb_strlen($currentChunk);
            } else {
                // Добавляем предложение к текущему чанку
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
                $currentLength += $sentenceLength + ($currentChunk ? 1 : 0);
            }
        }

        // Добавляем оставшийся чанк
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Очищает и нормализует текст для дальнейшей обработки
     */
    public function cleanText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        $text = str_replace(["\r\n", "\r"], "\n", (string) $text);

        return trim($text);
    }

    /**
     * Извлекает текст из файла различных форматов, поддерживает TXT, MD, HTML, PDF, DOCX и DOC файлы
     */
    public function extractTextFromFile(string $filePath, ?string $fileType = null): string
    {
        if ($fileType) {
            $extension = strtolower($fileType);
        } else {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }

        return match ($extension) {
            'txt' => $this->extractFromText($filePath),
            'md' => $this->extractFromMarkdown($filePath),
            'html' => $this->extractFromHtml($filePath),
            'pdf' => $this->extractFromPdf($filePath),
            'docx', 'doc' => $this->extractFromDocx($filePath),
            default => throw new InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    /**
     * Разбивает текст на отдельные предложения по знакам препинания (.!?)
     */
    private function splitIntoSentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($sentences, function ($sentence) {
            return trim($sentence) !== '';
        });
    }

    /**
     * Создает текст перекрытия для соседних чанков
     */
    private function getOverlapText(string $text): string
    {
        if (mb_strlen($text) <= $this->chunkOverlap) {
            return $text;
        }

        // Берем последние N символов для перекрытия с конца
        $overlapText = mb_substr($text, -$this->chunkOverlap);

        $lastSpace = mb_strrpos($overlapText, ' ');
        if ($lastSpace !== false) {
            $overlapText = mb_substr($overlapText, $lastSpace + 1);
        }

        return $overlapText;
    }

    /**
     * Извлекает чистый текст из Markdown файла
     */
    private function extractFromMarkdown(string $filePath): string
    {
        $content = file_get_contents($filePath);

        $content = preg_replace('/^#+\s*/m', '', $content);
        $content = preg_replace('/\*\*(.*?)\*\*/', '$1', $content);
        $content = preg_replace('/\*(.*?)\*/', '$1', $content);
        $content = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $content);
        $content = preg_replace('/`([^`]*)`/', '$1', $content);
        $content = preg_replace('/```[^`]*```/', '', $content);

        return $content;
    }

    /**
     * Извлекает чистый текст из HTML файла
     */
    private function extractFromHtml(string $filePath): string
    {
        $content = file_get_contents($filePath);

        $content = strip_tags($content);

        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        return $content;
    }
}
