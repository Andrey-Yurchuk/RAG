<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO\Response;

use RagSystem\Application\DTO\DTOInterface;

final class ApiResponseDTO implements DTOInterface
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $data = null,
        public readonly ?array $errors = null,
        public readonly int $code = 200
    ) {}

    /**
     * @inheritdoc
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            message: $data['message'] ?? '',
            data: $data['data'] ?? null,
            errors: $data['errors'] ?? null,
            code: (int) ($data['code'] ?? 200)
        );
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'message' => $this->message,
            'code' => $this->code
        ];
        
        if ($this->data !== null) {
            $result['data'] = $this->data;
        }
        
        if ($this->errors !== null) {
            $result['errors'] = $this->errors;
        }
        
        return $result;
    }
}
