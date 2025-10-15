<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO\Document;

use RagSystem\Application\DTO\DTOInterface;

final class UploadRequestDTO implements DTOInterface
{
    public function __construct(
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $title = null
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $data): self
    {
        return new self(
            filename: $data['filename'] ?? '',
            mimeType: $data['mime_type'] ?? $data['mimeType'] ?? '',
            size: (int) ($data['size'] ?? 0),
            title: $data['title'] ?? null
        );
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'title' => $this->title
        ];
    }
}
