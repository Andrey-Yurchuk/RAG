<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO\Document;

use RagSystem\Application\DTO\DTOInterface;

final class DocumentRequestDTO implements DTOInterface
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $content = null,
        public readonly ?string $metadata = null
    ) {}

    /**
     * @inheritdoc
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: trim($data['title'] ?? ''),
            content: $data['content'] ?? null,
            metadata: $data['metadata'] ?? null
        );
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'metadata' => $this->metadata
        ];
    }
}
