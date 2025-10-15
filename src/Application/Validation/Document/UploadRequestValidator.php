<?php

declare(strict_types=1);

namespace RagSystem\Application\Validation\Document;

use RagSystem\Application\DTO\Document\UploadRequestDTO;
use RagSystem\Application\Validation\ValidationResult;
use RagSystem\Application\Validation\ValidatorInterface;

final class UploadRequestValidator implements ValidatorInterface
{
    private const array ALLOWED_MIME_TYPES = [
        'text/plain',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    private int $maxFileSize;

    public function __construct(?int $maxFileSize = null)
    {
        if ($maxFileSize !== null) {
            $this->maxFileSize = $maxFileSize;
        } else {
            // Читаем из переменной окружения или 20MB по дефолту
            $this->maxFileSize = (int)($_ENV['MAX_UPLOAD_FILE_SIZE'] ?? 20 * 1024 * 1024);
        }
    }

    /**
     * @inheritdoc
     */
    public function validate(mixed $data): ValidationResult
    {
        if (!$data instanceof UploadRequestDTO) {
            return new ValidationResult(['data' => 'Expected UploadRequestDTO instance']);
        }

        $errors = [];

        if (empty($data->filename)) {
            $errors['filename'] = 'Filename is required';
        }

        if (empty($data->mimeType)) {
            $errors['mime_type'] = 'MIME type is required';
        }

        if ($data->size <= 0) {
            $errors['size'] = 'File size must be positive';
        }

        if ($data->size > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / (1024 * 1024);
            $errors['size'] = "File size must not exceed {$maxSizeMB}MB";
        }

        if (!in_array($data->mimeType, self::ALLOWED_MIME_TYPES)) {
            $errors['mime_type'] = 'Unsupported file type';
        }

        return new ValidationResult($errors);
    }
}
