<?php

declare(strict_types=1);

namespace RagSystem\Domain\Model;

use DateTimeImmutable;

final class UserSession
{
    private int $id;
    private DateTimeImmutable $createdAt;

    public function __construct(
        private int $userId,
        private string $sessionToken,
        private DateTimeImmutable $expiresAt,
        private ?string $ipAddress = null,
        private ?string $userAgent = null
    ) {
        $this->id = 0;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Создает сессию из массива данных
     */
    public static function fromArray(array $data): self
    {
        $session = new self(
            (int) $data['user_id'],
            $data['session_token'],
            new DateTimeImmutable($data['expires_at']),
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null
        );

        if (isset($data['id'])) {
            $session->id = (int) $data['id'];
        }

        if (isset($data['created_at'])) {
            $session->createdAt = new DateTimeImmutable($data['created_at']);
        }

        return $session;
    }

    /**
     * Проверяет, истекла ли сессия
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    /**
     * Проверяет, активна ли сессия
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Продлевает сессию на указанное количество секунд
     */
    public function extend(int $seconds): void
    {
        $this->expiresAt = new DateTimeImmutable("+{$seconds} seconds");
    }

    /**
     * Создает новую сессию для пользователя
     */
    public static function createForUser(
        int $userId,
        int $lifetimeSeconds = 3600,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable("+{$lifetimeSeconds} seconds");

        return new self($userId, $sessionToken, $expiresAt, $ipAddress, $userAgent);
    }

    /**
     * Обновляет информацию о сессии (IP, User Agent)
     */
    public function updateSessionInfo(?string $ipAddress = null, ?string $userAgent = null): void
    {
        if ($ipAddress !== null) {
            $this->ipAddress = $ipAddress;
        }
        if ($userAgent !== null) {
            $this->userAgent = $userAgent;
        }
    }

    /**
     * Возвращает id сессии
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Возвращает id пользователя
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Возвращает токен сессии
     */
    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    /**
     * Возвращает дату истечения сессии
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Возвращает дату создания сессии
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Возвращает IP адрес пользователя
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Возвращает User Agent пользователя
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Возвращает массив данных для сохранения в бд
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'session_token' => $this->sessionToken,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
