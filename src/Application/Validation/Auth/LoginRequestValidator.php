<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation\Auth;

use RagSystem\Application\DTO\Auth\LoginRequestDTO;
use RagSystem\Application\Validation\ValidationResult;
use RagSystem\Application\Validation\ValidatorInterface;

final class LoginRequestValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof LoginRequestDTO) {
            return new ValidationResult(['data' => 'Expected LoginRequestDTO instance']);
        }

        $errors = [];

        if (empty($data->username)) {
            $errors['username'] = 'Username is required';
        }

        if (empty($data->password)) {
            $errors['password'] = 'Password is required';
        }

        return new ValidationResult($errors);
    }
}
