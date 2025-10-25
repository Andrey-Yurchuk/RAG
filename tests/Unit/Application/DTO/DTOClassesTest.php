<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\DTO;

use PHPUnit\Framework\TestCase;
use RagSystem\Application\DTO\Auth\LoginRequestDTO;
use RagSystem\Application\DTO\Auth\LogoutRequestDTO;
use RagSystem\Application\DTO\Document\DocumentRequestDTO;
use RagSystem\Application\DTO\Document\UploadRequestDTO;
use RagSystem\Application\DTO\Query\QueryRequestDTO;
use RagSystem\Application\DTO\Response\ApiResponseDTO;

/**
 * @covers \RagSystem\Application\DTO\Auth\LoginRequestDTO
 * @covers \RagSystem\Application\DTO\Auth\LogoutRequestDTO
 * @covers \RagSystem\Application\DTO\Document\DocumentRequestDTO
 * @covers \RagSystem\Application\DTO\Document\UploadRequestDTO
 * @covers \RagSystem\Application\DTO\Query\QueryRequestDTO
 * @covers \RagSystem\Application\DTO\Response\ApiResponseDTO
 */
class DTOClassesTest extends TestCase
{
    /**
     * Тест создания LoginRequestDTO из массива
     */
    public function testLoginRequestDTOFromArray(): void
    {
        $data = [
            'username' => '  testuser  ',
            'password' => 'password123'
        ];
        
        $dto = LoginRequestDTO::fromArray($data);
        
        $this->assertEquals('testuser', $dto->username);
        $this->assertEquals('password123', $dto->password);
    }

    /**
     * Тест преобразования LoginRequestDTO в массив
     */
    public function testLoginRequestDTOToArray(): void
    {
        $dto = new LoginRequestDTO('testuser', 'password123');
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'username' => 'testuser',
            'password' => 'password123'
        ], $array);
    }

    /**
     * Тест создания LoginRequestDTO из массива с значениями по умолчанию
     */
    public function testLoginRequestDTOFromArrayWithDefaults(): void
    {
        $data = [];
        
        $dto = LoginRequestDTO::fromArray($data);
        
        $this->assertEquals('', $dto->username);
        $this->assertEquals('', $dto->password);
    }

    /**
     * Тест создания LogoutRequestDTO из массива
     */
    public function testLogoutRequestDTOFromArray(): void
    {
        $data = [
            'session_token' => 'abc123'
        ];
        
        $dto = LogoutRequestDTO::fromArray($data);
        
        $this->assertEquals('abc123', $dto->sessionToken);
    }

    /**
     * Тест преобразования LogoutRequestDTO в массив
     */
    public function testLogoutRequestDTOToArray(): void
    {
        $dto = new LogoutRequestDTO('abc123');
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'session_token' => 'abc123'
        ], $array);
    }

    /**
     * Тест создания LogoutRequestDTO из массива с значениями по умолчанию
     */
    public function testLogoutRequestDTOFromArrayWithDefaults(): void
    {
        $data = [];
        
        $dto = LogoutRequestDTO::fromArray($data);
        
        $this->assertEquals('', $dto->sessionToken);
    }

    /**
     * Тест создания DocumentRequestDTO из массива
     */
    public function testDocumentRequestDTOFromArray(): void
    {
        $data = [
            'title' => '  Test Document  ',
            'content' => 'Document content',
            'metadata' => '{"author": "test"}'
        ];
        
        $dto = DocumentRequestDTO::fromArray($data);
        
        $this->assertEquals('Test Document', $dto->title);
        $this->assertEquals('Document content', $dto->content);
        $this->assertEquals('{"author": "test"}', $dto->metadata);
    }

    /**
     * Тест преобразования DocumentRequestDTO в массив
     */
    public function testDocumentRequestDTOToArray(): void
    {
        $dto = new DocumentRequestDTO('Test Document', 'Document content', '{"author": "test"}');
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'title' => 'Test Document',
            'content' => 'Document content',
            'metadata' => '{"author": "test"}'
        ], $array);
    }

    /**
     * Тест создания DocumentRequestDTO из массива с значениями по умолчанию
     */
    public function testDocumentRequestDTOFromArrayWithDefaults(): void
    {
        $data = [];
        
        $dto = DocumentRequestDTO::fromArray($data);
        
        $this->assertEquals('', $dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->metadata);
    }

    /**
     * Тест создания DocumentRequestDTO через конструктор с значениями по умолчанию
     */
    public function testDocumentRequestDTOConstructorWithDefaults(): void
    {
        $dto = new DocumentRequestDTO('Test Document');
        
        $this->assertEquals('Test Document', $dto->title);
        $this->assertNull($dto->content);
        $this->assertNull($dto->metadata);
    }

    /**
     * Тест создания UploadRequestDTO из массива
     */
    public function testUploadRequestDTOFromArray(): void
    {
        $data = [
            'filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'size' => '1024',
            'title' => 'Test File'
        ];
        
        $dto = UploadRequestDTO::fromArray($data);
        
        $this->assertEquals('test.txt', $dto->filename);
        $this->assertEquals('text/plain', $dto->mimeType);
        $this->assertEquals(1024, $dto->size);
        $this->assertEquals('Test File', $dto->title);
    }

    /**
     * Тест создания UploadRequestDTO из массива с ключом mimeType
     */
    public function testUploadRequestDTOFromArrayWithMimeTypeKey(): void
    {
        $data = [
            'filename' => 'test.txt',
            'mimeType' => 'text/plain',
            'size' => '1024'
        ];
        
        $dto = UploadRequestDTO::fromArray($data);
        
        $this->assertEquals('text/plain', $dto->mimeType);
    }

    /**
     * Тест преобразования UploadRequestDTO в массив
     */
    public function testUploadRequestDTOToArray(): void
    {
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024, 'Test File');
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'size' => 1024,
            'title' => 'Test File'
        ], $array);
    }

    /**
     * Тест создания UploadRequestDTO из массива с значениями по умолчанию
     */
    public function testUploadRequestDTOFromArrayWithDefaults(): void
    {
        $data = [];
        
        $dto = UploadRequestDTO::fromArray($data);
        
        $this->assertEquals('', $dto->filename);
        $this->assertEquals('', $dto->mimeType);
        $this->assertEquals(0, $dto->size);
        $this->assertNull($dto->title);
    }

    /**
     * Тест создания UploadRequestDTO через конструктор с значениями по умолчанию
     */
    public function testUploadRequestDTOConstructorWithDefaults(): void
    {
        $dto = new UploadRequestDTO('test.txt', 'text/plain', 1024);
        
        $this->assertEquals('test.txt', $dto->filename);
        $this->assertEquals('text/plain', $dto->mimeType);
        $this->assertEquals(1024, $dto->size);
        $this->assertNull($dto->title);
    }

    /**
     * Тест создания QueryRequestDTO из массива
     */
    public function testQueryRequestDTOFromArray(): void
    {
        $data = [
            'query' => 'test query',
            'response_time' => '1.5'
        ];
        
        $dto = QueryRequestDTO::fromArray($data);
        
        $this->assertEquals('test query', $dto->query);
        $this->assertEquals(1.5, $dto->responseTime);
    }

    /**
     * Тест преобразования QueryRequestDTO в массив
     */
    public function testQueryRequestDTOToArray(): void
    {
        $dto = new QueryRequestDTO('test query', 1.5);
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'query' => 'test query',
            'response_time' => 1.5
        ], $array);
    }

    /**
     * Тест создания QueryRequestDTO из массива с значениями по умолчанию
     */
    public function testQueryRequestDTOFromArrayWithDefaults(): void
    {
        $data = [];
        
        $dto = QueryRequestDTO::fromArray($data);
        
        $this->assertEquals('', $dto->query);
        $this->assertNull($dto->responseTime);
    }

    /**
     * Тест создания QueryRequestDTO через конструктор с значениями по умолчанию
     */
    public function testQueryRequestDTOConstructorWithDefaults(): void
    {
        $dto = new QueryRequestDTO('test query');
        
        $this->assertEquals('test query', $dto->query);
        $this->assertNull($dto->responseTime);
    }

    /**
     * Тест создания ApiResponseDTO из массива
     */
    public function testApiResponseDTOFromArray(): void
    {
        $data = [
            'success' => '1',
            'message' => 'Success message',
            'data' => ['key' => 'value'],
            'errors' => ['field' => 'error'],
            'code' => '201'
        ];
        
        $dto = ApiResponseDTO::fromArray($data);
        
        $this->assertTrue($dto->success);
        $this->assertEquals('Success message', $dto->message);
        $this->assertEquals(['key' => 'value'], $dto->data);
        $this->assertEquals(['field' => 'error'], $dto->errors);
        $this->assertEquals(201, $dto->code);
    }

    /**
     * Тест преобразования ApiResponseDTO в массив
     */
    public function testApiResponseDTOToArray(): void
    {
        $dto = new ApiResponseDTO(true, 'Success message', ['key' => 'value'], ['field' => 'error'], 201);
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'success' => true,
            'message' => 'Success message',
            'code' => 201,
            'data' => ['key' => 'value'],
            'errors' => ['field' => 'error']
        ], $array);
    }

    /**
     * Тест создания ApiResponseDTO из массива с значениями по умолчанию
     */
    public function testApiResponseDTOFromArrayWithDefaults(): void
    {
        $data = [];
        
        $dto = ApiResponseDTO::fromArray($data);
        
        $this->assertFalse($dto->success);
        $this->assertEquals('', $dto->message);
        $this->assertNull($dto->data);
        $this->assertNull($dto->errors);
        $this->assertEquals(200, $dto->code);
    }

    /**
     * Тест создания ApiResponseDTO через конструктор с значениями по умолчанию
     */
    public function testApiResponseDTOConstructorWithDefaults(): void
    {
        $dto = new ApiResponseDTO(true, 'Success message');
        
        $this->assertTrue($dto->success);
        $this->assertEquals('Success message', $dto->message);
        $this->assertNull($dto->data);
        $this->assertNull($dto->errors);
        $this->assertEquals(200, $dto->code);
    }

    /**
     * Тест преобразования ApiResponseDTO в массив без опциональных полей
     */
    public function testApiResponseDTOToArrayWithoutOptionalFields(): void
    {
        $dto = new ApiResponseDTO(true, 'Success message');
        
        $array = $dto->toArray();
        
        $this->assertEquals([
            'success' => true,
            'message' => 'Success message',
            'code' => 200
        ], $array);
        $this->assertArrayNotHasKey('data', $array);
        $this->assertArrayNotHasKey('errors', $array);
    }
}