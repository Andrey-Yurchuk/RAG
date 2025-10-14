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
        private ?string $response = null,
        private ?float $responseTime = null
    ) {
        $this->id = Uuid::uuid4();
        $this->createdAt = new DateTimeImmutable();
    }

    public static function fromArray(array $data): self
    {
        $query = new self(
            $data['queryText'] ?? $data['query_text'] ?? '',
            $data['queryEmbedding'] ?? $data['query_embedding'] ?? null,
            $data['response'] ?? null,
            isset($data['responseTime']) || isset($data['response_time']) 
                ? (float)($data['responseTime'] ?? $data['response_time']) 
                : null
        );

        if (isset($data['id'])) {
            $query->id = Uuid::fromString($data['id']);
        }

        if (isset($data['createdAt']) || isset($data['created_at'])) {
            $createdAt = $data['createdAt'] ?? $data['created_at'];
            $query->createdAt = new DateTimeImmutable($createdAt);
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

    public function getResponseTime(): ?float
    {
        return $this->responseTime;
    }

    public function setResponseTime(?float $responseTime): void
    {
        $this->responseTime = $responseTime;
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
            'responseTime' => $this->responseTime,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}