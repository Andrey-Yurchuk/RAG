<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Http;

class Response
{
    public function __construct(
        private string $body = '',
        private int $statusCode = 200,
        private array $headers = []
    ) {
    }

    /**
     * Создает JSON ответ
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Создает HTML ответ
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        return new self(
            $html,
            $statusCode,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Создает текстовый ответ
     */
    public static function text(string $text, int $statusCode = 200): self
    {
        return new self(
            $text,
            $statusCode,
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Создает ответ с ошибкой
     */
    public static function error(string $message, int $statusCode = 500): self
    {
        return self::json([
            'error' => $message,
            'status' => $statusCode
        ], $statusCode);
    }

    /**
     * Создает ответ "Не найдено" (404)
     */
    public static function notFound(string $message = 'Not Found'): self
    {
        return self::error($message, 404);
    }

    /**
     * Создает ответ "Неверный запрос" (400)
     */
    public static function badRequest(string $message = 'Bad Request'): self
    {
        return self::error($message, 400);
    }

    /**
     * Создает ответ "Не авторизован" (401)
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::error($message, 401);
    }

    /**
     * Создает ответ "Запрещено" (403)
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::error($message, 403);
    }

    /**
     * Создает ответ "Внутренняя ошибка сервера" (500)
     */
    public static function internalServerError(string $message = 'Internal Server Error'): self
    {
        return self::error($message, 500);
    }

    /**
     * Получает HTTP статус код
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Получает HTTP заголовки
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Получает тело ответа
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Добавляет или изменяет заголовок
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Изменяет статус код
     */
    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;

        return $clone;
    }
}
