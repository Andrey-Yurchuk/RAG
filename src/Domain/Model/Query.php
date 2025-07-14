<?php

declare(strict_types=1);

namespace RagSystem\Domain\Model;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Query
{
    private UuidInterface $id;
    private DateTimeImmutable $createdAt;

    public function __construct(
        private string $queryText,
        private ?array $queryEmbedding = null,
        private ?string $response = null
    ) {
        $this->id = Uuid::uuid4();
        $this->createdAt = new DateTimeImmutable();
    }

    public static function fromArray(array $data): self
    {
        $query = new self(
            $data['queryText'],
            $data['queryEmbedding'] ?? null,
            $data['response'] ?? null
        );

        if (isset($data['id'])) {
            $query->id = Uuid::fromString($data['id']);
        }

        if (isset($data['createdAt'])) {
            $query->createdAt = new DateTimeImmutable($data['createdAt']);
        }

        return $query;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getQueryText(): string
    {
        return $this->queryText;
    }

    public function getQueryEmbedding(): ?array
    {
        return $this->queryEmbedding;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setQueryEmbedding(?array $queryEmbedding): void
    {
        $this->queryEmbedding = $queryEmbedding;
    }

    public function setResponse(?string $response): void
    {
        $this->response = $response;
    }

    public function hasEmbedding(): bool
    {
        return $this->queryEmbedding !== null;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'queryText' => $this->queryText,
            'queryEmbedding' => $this->queryEmbedding,
            'response' => $this->response,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}