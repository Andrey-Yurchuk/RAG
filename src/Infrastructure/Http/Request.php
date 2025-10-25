<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Http;

class Request
{
    public function __construct(
        private string $method,
        private string $uri,
        private array $headers = [],
        private array $query = [],
        private array $body = [],
        private array $files = [],
        private array $pathParams = []
    ) {
        $this->method = strtoupper($method);
    }

    /**
     * Создает объект Request из глобальных переменных PHP
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = getallheaders() ?: [];
        $query = $_GET;
        $files = $_FILES;

        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            if (empty($input)) {
                $body = [];
            } else {
                $body = json_decode($input, true, 512, JSON_THROW_ON_ERROR) ?? [];
            }
        } else {
            $body = $_POST;
        }

        return new self($method, $uri, $headers, $query, $body, $files);
    }

    /**
     * Создает объект Request с параметрами пути
     */
    public static function withPathParams(array $pathParams): self
    {
        $request = self::fromGlobals();
        $request->pathParams = $pathParams;
        return $request;
    }

    /**
     * Создает новый Request с добавленными параметрами пути
     */
    public function withAddedPathParams(array $pathParams): self
    {
        $newRequest = new self(
            $this->method,
            $this->uri,
            $this->headers,
            $this->query,
            $this->body,
            $this->files,
            $pathParams
        );
        return $newRequest;
    }

    /**
     * Получает HTTP метод запроса
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Получает URI запроса
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Получает все HTTP заголовки
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Получает значение конкретного заголовка
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Получает все параметры запроса из URL
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Получает значение конкретного параметра из URL
     */
    public function getQueryParam(string $name): ?string
    {
        return $this->query[$name] ?? null;
    }

    /**
     * Получает все данные тела запроса
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Получает значение конкретного параметра из тела запроса
     */
    public function getBodyParam(string $name): mixed
    {
        return $this->body[$name] ?? null;
    }

    /**
     * Получает все загруженные файлы
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Получает конкретный загруженный файл
     */
    public function getFile(string $name): ?array
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Проверяет, является ли запрос JSON
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type') ?? '';
        return str_contains($contentType, 'application/json');
    }

    /**
     * Проверяет, является ли метод запроса POST
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Проверяет, является ли метод запроса GET
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Проверяет, является ли метод запроса PUT
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    /**
     * Проверяет, является ли метод запроса DELETE
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    /**
     * Получает все параметры пути
     */
    public function getPathParams(): array
    {
        return $this->pathParams;
    }

    /**
     * Получает конкретный параметр пути
     */
    public function getPathParam(string $name): ?string
    {
        return $this->pathParams[$name] ?? null;
    }
}
