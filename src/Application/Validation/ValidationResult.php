<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation;

final class ValidationResult
{
    public function __construct(
        private array $errors = []
    ) {
    }

    /**
     * Проверяет, прошла ли валидация успешно
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Получает массив ошибок валидации
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получает первую ошибку валидации
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Получает ошибку для конкретного поля
     */
    public function getFieldError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Проверяет, есть ли ошибка для конкретного поля
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Добавляет ошибку для поля
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Добавляет несколько ошибок
     */
    public function addErrors(array $errors): void
    {
        $this->errors = array_merge($this->errors, $errors);
    }
}
