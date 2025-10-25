<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Validation\Document;

use PHPUnit\Framework\TestCase;
use RagSystem\Application\DTO\Document\UploadRequestDTO;
use RagSystem\Application\Validation\Document\UploadRequestValidator;
use RagSystem\Application\Validation\ValidationResult;

/**
 * @covers \RagSystem\Application\Validation\Document\UploadRequestValidator
 * @covers \RagSystem\Application\DTO\Document\UploadRequestDTO
 * @covers \RagSystem\Application\Validation\ValidationResult
 */
class UploadRequestValidatorTest extends TestCase
{
    /**
     * Тест валидации корректного UploadRequestDTO
     */
    public function testUploadRequestValidatorValid(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации UploadRequestDTO с заголовком
     */
    public function testUploadRequestValidatorWithTitle(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024, 'Test Document');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации UploadRequestDTO с пустым именем файла
     */
    public function testUploadRequestValidatorEmptyFilename(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('', 'text/plain', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('filename', $result->getErrors());
        $this->assertEquals('Filename is required', $result->getErrors()['filename']);
    }

    /**
     * Тест валидации UploadRequestDTO с пустым MIME типом
     */
    public function testUploadRequestValidatorEmptyMimeType(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('test.txt', '', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('mime_type', $result->getErrors());
        $this->assertEquals('Unsupported file type', $result->getErrors()['mime_type']);
    }

    /**
     * Тест валидации UploadRequestDTO с нулевым размером файла
     */
    public function testUploadRequestValidatorZeroSize(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 0);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('size', $result->getErrors());
        $this->assertEquals('File size must be positive', $result->getErrors()['size']);
    }

    /**
     * Тест валидации UploadRequestDTO с отрицательным размером файла
     */
    public function testUploadRequestValidatorNegativeSize(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('test.txt', 'text/plain', -1);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('size', $result->getErrors());
        $this->assertEquals('File size must be positive', $result->getErrors()['size']);
    }

    /**
     * Тест валидации UploadRequestDTO с файлом слишком большого размера
     */
    public function testUploadRequestValidatorFileTooLarge(): void
    {
        $validator = new UploadRequestValidator(1024); // 1KB max
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 2048);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('size', $result->getErrors());
        $this->assertStringContainsString('File size must not exceed', $result->getErrors()['size']);
    }

    /**
     * Тест валидации UploadRequestDTO с неподдерживаемым MIME типом
     */
    public function testUploadRequestValidatorUnsupportedMimeType(): void
    {
        $validator = new UploadRequestValidator();
        $dto = new UploadRequestDTO('test.exe', 'application/x-executable', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('mime_type', $result->getErrors());
        $this->assertEquals('Unsupported file type', $result->getErrors()['mime_type']);
    }

    /**
     * Тест валидации UploadRequestDTO с разрешенными MIME типами
     */
    public function testUploadRequestValidatorAllowedMimeTypes(): void
    {
        $validator = new UploadRequestValidator();
        
        $allowedTypes = [
            'text/plain',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        foreach ($allowedTypes as $mimeType) {
            $dto = new UploadRequestDTO('test.' . pathinfo($mimeType, PATHINFO_EXTENSION), $mimeType, 1024);
            $result = $validator->validate($dto);
            
            $this->assertTrue($result->isValid(), "MIME type {$mimeType} should be allowed");
        }
    }

    /**
     * Тест валидации UploadRequestDTO с множественными ошибками
     */
    public function testUploadRequestValidatorMultipleErrors(): void
    {
        $validator = new UploadRequestValidator(1024);
        $dto = new UploadRequestDTO('', 'application/x-executable', 2048);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('filename', $result->getErrors());
        $this->assertArrayHasKey('mime_type', $result->getErrors());
        $this->assertArrayHasKey('size', $result->getErrors());
    }

    /**
     * Тест валидации UploadRequestDTO с неверными данными
     */
    public function testUploadRequestValidatorInvalidData(): void
    {
        $validator = new UploadRequestValidator();
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected UploadRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест конструктора UploadRequestValidator с максимальным размером
     */
    public function testUploadRequestValidatorConstructorWithMaxSize(): void
    {
        $validator = new UploadRequestValidator(512);
        
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 256);
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024);
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('size', $result->getErrors());
    }

    /**
     * Тест конструктора UploadRequestValidator с null значением
     */
    public function testUploadRequestValidatorConstructorWithNull(): void
    {
        $validator = new UploadRequestValidator(null);
        
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024);
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
    }
}