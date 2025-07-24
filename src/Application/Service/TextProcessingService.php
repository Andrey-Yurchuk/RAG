<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

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
}
