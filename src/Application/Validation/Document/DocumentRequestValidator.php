<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation\Document;

use RagSystem\Application\DTO\Document\DocumentRequestDTO;
use RagSystem\Application\Validation\ValidationResult;
use RagSystem\Application\Validation\ValidatorInterface;

final class DocumentRequestValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof DocumentRequestDTO) {
            return new ValidationResult(['data' => 'Expected DocumentRequestDTO instance']);
        }

        $errors = [];

        if (empty($data->title)) {
            $errors['title'] = 'Title is required';
        }

        if (strlen($data->title) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters';
        }

        return new ValidationResult($errors);
    }
}
