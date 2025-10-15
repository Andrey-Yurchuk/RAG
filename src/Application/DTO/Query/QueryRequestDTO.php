<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO\Query;

use RagSystem\Application\DTO\DTOInterface;

final class QueryRequestDTO implements DTOInterface
{
    public function __construct(
        public readonly string $query,
        public readonly ?float $responseTime = null
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'] ?? '',
            responseTime: isset($data['response_time']) ? (float) $data['response_time'] : null
        );
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'response_time' => $this->responseTime
        ];
    }
}
