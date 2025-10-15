<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use Exception;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use Psr\Log\LoggerInterface;
use RuntimeException;

class EmbeddingService
{
    public function __construct(
        private LlamaCppAdapter $llamaCppAdapter,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Генерирует векторное представление для текста
     */
    public function generateEmbedding(string $text): array
    {
        try {
            return $this->llamaCppAdapter->generateEmbedding($text);
        } catch (Exception $e) {
            $this->logger->error('Failed to generate embedding', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text)
            ]);

            throw new RuntimeException('Failed to generate embedding: ' . $e->getMessage());
        }
    }

    /**
     * Генерирует векторные представления для массива текстов
     */
    public function generateEmbeddings(array $texts): array
    {
        $embeddings = [];

        foreach ($texts as $text) {
            try {
                $embeddings[] = $this->generateEmbedding($text);
            } catch (Exception $e) {
                $this->logger->error('Failed to generate embedding in batch', [
                    'text' => substr($text, 0, 100) . '...',
                    'error' => $e->getMessage()
                ]);

                $embeddings[] = null;
            }
        }

        return $embeddings;
    }

    /**
     * Проверяет готовность работы эмбеддингов
     */
    public function checkHealth(): bool
    {
        return $this->llamaCppAdapter->ensureModelLoaded();
    }
}
