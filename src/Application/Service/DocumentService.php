<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use Exception;
use RagSystem\Domain\Model\Document;
use RagSystem\Domain\Model\DocumentChunk;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\TextProcessingService;
use Ramsey\Uuid\UuidInterface;
use Psr\Log\LoggerInterface;

class DocumentService
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private EmbeddingService $embeddingService,
        private TextProcessingService $textProcessingService,
        private LoggerInterface $logger
    ) {}

    /**
     * Создает новый документ и обрабатывает его чанки
     */
    public function createDocument(
        string $title,
        string $content,
        ?string $filePath = null,
        ?string $fileType = null
    ): Document {
        $document = new Document($title, $content, $filePath, $fileType);
        $this->documentRepository->save($document);
        $this->logger->info('Document created', ['document_id' => $document->getId()->toString()]);

        $this->processDocumentChunks($document);

        return $document;
    }

    /**
     * Получает документ по ID
     */
    public function getDocument(UuidInterface $id): Document
    {
        return $this->documentRepository->findById($id);
    }

    /**
     * Получает документы с пагинацией
     *
     * @param int $limit Лимит документов (максимальное количество для возврата)
     * @param int $offset Смещение (количество документов для пропуска)
     * @return array Массив документов
     */
    public function getAllDocuments(int $limit = 10, int $offset = 0): array
    {
        return $this->documentRepository->findAll($limit, $offset);
    }

    /**
     * Обновляет существующий документ и перерабатывает его чанки
     */
    public function updateDocument(UuidInterface $id, string $title, string $content): ?Document
    {
        $document = $this->documentRepository->findById($id);

        if (!$document) {
            return null;
        }

        $document->updateTitle($title);
        $document->updateContent($content);

        $this->documentRepository->save($document);
        $this->logger->info('Document updated', ['document_id' => $id->toString()]);

        // Переработка (обновление) чанков
        $this->documentRepository->deleteChunksByDocumentId($id);
        $this->processDocumentChunks($document);

        return $document;
    }

    /**
     * Удаляет документ по ID
     */
    public function deleteDocument(UuidInterface $id): bool
    {
        $success = $this->documentRepository->delete($id);

        if ($success) {
            $this->logger->info('Document deleted', ['document_id' => $id->toString()]);
        }

        return $success;
    }

    /**
     * Поиск похожих документов по векторному сходству
     *
     * @param string $query Поисковый запрос
     * @param int $limit Максимальное количество результатов
     * @param float $threshold Порог схожести от 0.0 до 1.0
     * @return array Массив найденных фрагментов документов
     */
    public function searchSimilarDocuments(string $query, int $limit = 10, float $threshold = 0.8): array
    {
        $queryEmbedding = $this->embeddingService->generateEmbedding($query);

        return $this->documentRepository->searchSimilarChunks($queryEmbedding, $limit, $threshold);
    }

    /**
     * Обрабатывает документ, разбивая его на чанки и генерируя эмбеддинги
     */
    private function processDocumentChunks(Document $document): void
    {
        try {
            $chunks = $this->textProcessingService->chunkText($document->getContent());

            foreach ($chunks as $index => $chunkText) {
                $chunk = new DocumentChunk(
                    $document->getId(),
                    $chunkText,
                    $index
                );

                // Генерация эмбеддингов на основе чанков
                $embedding = $this->embeddingService->generateEmbedding($chunkText);
                $chunk->setEmbedding($embedding);

                $this->documentRepository->saveChunk($chunk);
                $document->addChunk($chunk);
            }

            $this->logger->info('Document chunks processed', [
                'document_id' => $document->getId()->toString(),
                'chunks_count' => count($chunks)
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to process document chunks', [
                'document_id' => $document->getId()->toString(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
