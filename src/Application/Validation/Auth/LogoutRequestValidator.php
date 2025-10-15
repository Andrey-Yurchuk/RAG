<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation\Auth;

use RagSystem\Application\DTO\Auth\LogoutRequestDTO;
use RagSystem\Application\Validation\ValidationResult;
use RagSystem\Application\Validation\ValidatorInterface;

final class LogoutRequestValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof LogoutRequestDTO) {
            return new ValidationResult(['data' => 'Expected LogoutRequestDTO instance']);
        }

        $errors = [];

        if (empty($data->sessionToken)) {
            $errors['session_token'] = 'Session token is required';
        }

        return new ValidationResult($errors);
    }
}
