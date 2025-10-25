<?php

declare(strict_types=1);

namespace RagSystem\Domain\Model;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Document
{
    private UuidInterface $id;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    /** @var DocumentChunk[] */
    private array $chunks = [];

    public function __construct(
        private string $title,
        private string $content,
        private ?string $filePath = null,
        private ?string $fileType = null
    ) {
        $this->id = Uuid::uuid4();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function fromArray(array $data): self
    {
        $document = new self(
            $data['title'],
            $data['content'],
            $data['file_path'] ?? null,
            $data['file_type'] ?? null,
        );

        if (isset($data['id'])) {
            $document->id = Uuid::fromString($data['id']);
        }

        if (isset($data['createdAt'])) {
            $document->createdAt = new DateTimeImmutable($data['createdAt']);
        }

        if (isset($data['updatedAt'])) {
            $document->updatedAt = new DateTimeImmutable($data['updatedAt']);
        }

        return $document;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getFileType(): ?string
    {
        return $this->fileType;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getChunks(): array
    {
        return $this->chunks;
    }

    public function updateContent(string $content): void
    {
        $this->content = $content;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateTitle(string $title): void
    {
        $this->title = $title;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addChunk(DocumentChunk $chunk): void
    {
        $this->chunks[] = $chunk;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'title' => $this->title,
            'content' => $this->content,
            'file_path' => $this->filePath,
            'file_type' => $this->fileType,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
