<?php

declare(strict_types=1);

namespace RagSystem\Domain\Model;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DocumentChunk
{
    private UuidInterface $id;
    private DateTimeImmutable $createdAt;

    public function __construct(
        private UuidInterface $documentId,
        private string $chunkText,
        private int $chunkIndex,
        private ?array $embedding = null
    ) {
        $this->id = Uuid::uuid4();
        $this->createdAt = new DateTimeImmutable();
    }

    public static function fromArray(array $data): self
    {
        $chunk = new self(
            Uuid::fromString($data['document_id']),
            $data['chunk_text'],
            (int) $data['chunk_index'],
            $data['embedding'] ?? null,
        );

        if (isset($data['id']) && $data['id']) {
            $chunk->id = Uuid::fromString($data['id']);
        }

        if (isset($data['createdAt']) && $data['createdAt']) {
            $chunk->createdAt = new DateTimeImmutable($data['createdAt']);
        }

        return $chunk;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getDocumentId(): UuidInterface
    {
        return $this->documentId;
    }

    public function getChunkText(): string
    {
        return $this->chunkText;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function getEmbedding(): ?array
    {
        return $this->embedding;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setEmbedding(?array $embedding): void
    {
        $this->embedding = $embedding;
    }

    public function hasEmbedding(): bool
    {
        return $this->embedding !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'document_id' => $this->documentId->toString(),
            'chunk_text' => $this->chunkText,
            'chunk_index' => $this->chunkIndex,
            'embedding' => $this->embedding,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
