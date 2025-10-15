<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO\Auth;

use RagSystem\Application\DTO\DTOInterface;

final class LoginRequestDTO implements DTOInterface
{
    public function __construct(
        public readonly string $username,
        public readonly string $password
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: trim($data['username'] ?? ''),
            password: $data['password'] ?? ''
        );
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password
        ];
    }
}
