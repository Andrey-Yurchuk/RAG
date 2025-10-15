<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation;

interface ValidatorInterface
{
    /**
     * Валидирует данные и возвращает результат валидации
     */
    public function validate(mixed $data): ValidationResult;
}
