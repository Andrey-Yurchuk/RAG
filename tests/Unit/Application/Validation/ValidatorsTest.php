<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Validation;

use PHPUnit\Framework\TestCase;
use RagSystem\Application\DTO\Auth\LoginRequestDTO;
use RagSystem\Application\DTO\Auth\LogoutRequestDTO;
use RagSystem\Application\DTO\Document\DocumentRequestDTO;
use RagSystem\Application\DTO\Document\UploadRequestDTO;
use RagSystem\Application\DTO\Query\QueryRequestDTO;
use RagSystem\Application\Validation\Auth\LoginRequestValidator;
use RagSystem\Application\Validation\Auth\LogoutRequestValidator;
use RagSystem\Application\Validation\Document\DocumentRequestValidator;
use RagSystem\Application\Validation\Document\UploadRequestValidator;
use RagSystem\Application\Validation\Query\QueryRequestValidator;
use RagSystem\Application\Validation\ValidationResult;

/**
 * @covers \RagSystem\Application\Validation\Auth\LoginRequestValidator
 * @covers \RagSystem\Application\Validation\Auth\LogoutRequestValidator
 * @covers \RagSystem\Application\Validation\Document\DocumentRequestValidator
 * @covers \RagSystem\Application\Validation\Document\UploadRequestValidator
 * @covers \RagSystem\Application\Validation\Query\QueryRequestValidator
 * @covers \RagSystem\Application\Validation\ValidationResult
 * @covers \RagSystem\Application\DTO\Auth\LoginRequestDTO
 * @covers \RagSystem\Application\DTO\Auth\LogoutRequestDTO
 * @covers \RagSystem\Application\DTO\Document\DocumentRequestDTO
 * @covers \RagSystem\Application\DTO\Document\UploadRequestDTO
 * @covers \RagSystem\Application\DTO\Query\QueryRequestDTO
 */
class ValidatorsTest extends TestCase
{
    /**
     * Тест валидации корректных данных LoginRequestValidator
     */
    public function testLoginRequestValidatorValidData(): void
    {
        $validator = new LoginRequestValidator();
        $dto = new LoginRequestDTO('testuser', 'password123');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации LoginRequestValidator с пустым именем пользователя
     */
    public function testLoginRequestValidatorEmptyUsername(): void
    {
        $validator = new LoginRequestValidator();
        $dto = new LoginRequestDTO('', 'password123');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('username', $result->getErrors());
        $this->assertEquals('Username is required', $result->getErrors()['username']);
    }

    /**
     * Тест валидации LoginRequestValidator с пустым паролем
     */
    public function testLoginRequestValidatorEmptyPassword(): void
    {
        $validator = new LoginRequestValidator();
        $dto = new LoginRequestDTO('testuser', '');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('password', $result->getErrors());
        $this->assertEquals('Password is required', $result->getErrors()['password']);
    }

    /**
     * Тест валидации LoginRequestValidator с неверным типом данных
     */
    public function testLoginRequestValidatorInvalidDataType(): void
    {
        $validator = new LoginRequestValidator();
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected LoginRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест валидации корректных данных LogoutRequestValidator
     */
    public function testLogoutRequestValidatorValidData(): void
    {
        $validator = new LogoutRequestValidator();
        $dto = new LogoutRequestDTO('session123');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации LogoutRequestValidator с пустым токеном
     */
    public function testLogoutRequestValidatorEmptyToken(): void
    {
        $validator = new LogoutRequestValidator();
        $dto = new LogoutRequestDTO('');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('session_token', $result->getErrors());
        $this->assertEquals('Session token is required', $result->getErrors()['session_token']);
    }

    /**
     * Тест валидации LogoutRequestValidator с неверным типом данных
     */
    public function testLogoutRequestValidatorInvalidDataType(): void
    {
        $validator = new LogoutRequestValidator();
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected LogoutRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест валидации корректных данных DocumentRequestValidator
     */
    public function testDocumentRequestValidatorValidData(): void
    {
        $validator = new DocumentRequestValidator();
        $dto = new DocumentRequestDTO('Test Document', 'Content');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации DocumentRequestValidator с пустым заголовком
     */
    public function testDocumentRequestValidatorEmptyTitle(): void
    {
        $validator = new DocumentRequestValidator();
        $dto = new DocumentRequestDTO('', 'Content');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('title', $result->getErrors());
        $this->assertEquals('Title is required', $result->getErrors()['title']);
    }

    /**
     * Тест валидации DocumentRequestValidator с заголовком слишком большой длины
     */
    public function testDocumentRequestValidatorTitleTooLong(): void
    {
        $validator = new DocumentRequestValidator();
        $longTitle = str_repeat('a', 256);
        $dto = new DocumentRequestDTO($longTitle, 'Content');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('title', $result->getErrors());
        $this->assertEquals('Title must not exceed 255 characters', $result->getErrors()['title']);
    }

    /**
     * Тест валидации DocumentRequestValidator с неверным типом данных
     */
    public function testDocumentRequestValidatorInvalidDataType(): void
    {
        $validator = new DocumentRequestValidator();
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected DocumentRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест валидации корректных данных UploadRequestValidator
     */
    public function testUploadRequestValidatorValidData(): void
    {
        $validator = new UploadRequestValidator(1024 * 1024); // 1MB max
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации UploadRequestValidator с пустым именем файла
     */
    public function testUploadRequestValidatorEmptyFilename(): void
    {
        $validator = new UploadRequestValidator(1024 * 1024);
        $dto = new UploadRequestDTO('', 'text/plain', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('filename', $result->getErrors());
        $this->assertEquals('Filename is required', $result->getErrors()['filename']);
    }

    /**
     * Тест валидации UploadRequestValidator с неподдерживаемым MIME типом
     */
    public function testUploadRequestValidatorUnsupportedMimeType(): void
    {
        $validator = new UploadRequestValidator(1024 * 1024);
        $dto = new UploadRequestDTO('test.exe', 'application/x-executable', 1024);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('mime_type', $result->getErrors());
        $this->assertEquals('Unsupported file type', $result->getErrors()['mime_type']);
    }

    /**
     * Тест валидации UploadRequestValidator с файлом слишком большого размера
     */
    public function testUploadRequestValidatorFileTooLarge(): void
    {
        $validator = new UploadRequestValidator(1024); // 1KB max
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 2048);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('size', $result->getErrors());
        $this->assertEquals('File size must not exceed 0.0009765625MB', $result->getErrors()['size']);
    }

    /**
     * Тест валидации UploadRequestValidator с неверным типом данных
     */
    public function testUploadRequestValidatorInvalidDataType(): void
    {
        $validator = new UploadRequestValidator(1024 * 1024);
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected UploadRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест валидации корректных данных QueryRequestValidator
     */
    public function testQueryRequestValidatorValidData(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestValidator с пустым запросом
     */
    public function testQueryRequestValidatorEmptyQuery(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('query', $result->getErrors());
        $this->assertEquals('Query cannot be empty', $result->getErrors()['query']);
    }

    /**
     * Тест валидации QueryRequestValidator с неверным типом данных
     */
    public function testQueryRequestValidatorInvalidDataType(): void
    {
        $validator = new QueryRequestValidator();
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected QueryRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест создания валидного ValidationResult
     */
    public function testValidationResultValid(): void
    {
        $result = new ValidationResult([]);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест создания невалидного ValidationResult
     */
    public function testValidationResultInvalid(): void
    {
        $errors = ['field' => 'error message'];
        $result = new ValidationResult($errors);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals($errors, $result->getErrors());
    }

    /**
     * Тест добавления ошибки в ValidationResult
     */
    public function testValidationResultAddError(): void
    {
        $result = new ValidationResult([]);
        
        $result->addError('field', 'error message');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('field', $result->getErrors());
        $this->assertEquals('error message', $result->getErrors()['field']);
    }
}