<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO\Auth;

use RagSystem\Application\DTO\DTOInterface;

final class LogoutRequestDTO implements DTOInterface
{
    public function __construct(
        public readonly string $sessionToken
    ) {}

    /**
     * @inheritdoc
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionToken: $data['session_token'] ?? ''
        );
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'session_token' => $this->sessionToken
        ];
    }
}
