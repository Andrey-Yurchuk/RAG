<?php

declare(strict_types=1);

namespace RagSystem\Application\Factory;

use RagSystem\Application\DTO\Response\ApiResponseDTO;

final class ApiResponseFactory
{
    /**
     * Создает успешный ответ API
     */
    public static function success(string $message, ?array $data = null, int $code = 200): ApiResponseDTO
    {
        return new ApiResponseDTO(
            success: true,
            message: $message,
            data: $data,
            code: $code
        );
    }

    /**
     * Создает ошибочный ответ API
     */
    public static function error(string $message, ?array $errors = null, int $code = 400): ApiResponseDTO
    {
        return new ApiResponseDTO(
            success: false,
            message: $message,
            errors: $errors,
            code: $code
        );
    }
}
