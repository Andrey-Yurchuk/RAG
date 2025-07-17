<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

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

    public function getDocument(UuidInterface $id): Document
    {
        return $this->documentRepository->findById($id);
    }

    public function getAllDocuments(int $limit = 10, int $offset = 0): array
    {
        return $this->documentRepository->findAll($limit, $offset);
    }
}
