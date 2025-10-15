<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation\Query;

use RagSystem\Application\DTO\Query\QueryRequestDTO;
use RagSystem\Application\Validation\ValidationResult;
use RagSystem\Application\Validation\ValidatorInterface;

final class QueryRequestValidator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof QueryRequestDTO) {
            return new ValidationResult(['data' => 'Expected QueryRequestDTO instance']);
        }

        $errors = [];

        if (empty(trim($data->query))) {
            $errors['query'] = 'Query cannot be empty';
        }

        if ($data->responseTime !== null && $data->responseTime < 0) {
            $errors['response_time'] = 'Response time must be positive';
        }

        return new ValidationResult($errors);
    }
}
