<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Factory;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Factory\ApiResponseFactory;

/**
 * Тесты для ApiResponseFactory
 * 
 * @covers \RagSystem\Application\Factory\ApiResponseFactory
 * @covers \RagSystem\Application\DTO\Response\ApiResponseDTO
 */
class ApiResponseFactoryTest extends BaseTestCase
{
    private ApiResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ApiResponseFactory();
    }

    /**
     * Тест создания успешного ответа
     */
    public function testCreateSuccessResponse(): void
    {
        $message = 'Success';
        $data = ['message' => 'Success'];
        $response = ApiResponseFactory::success($message, $data);

        $this->assertTrue($response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals($data, $response->data);
        $this->assertEquals(200, $response->code);
    }

    /**
     * Тест создания ответа с ошибкой
     */
    public function testCreateErrorResponse(): void
    {
        $message = 'Error occurred';
        $errors = ['field' => 'Ошибка в поле'];
        $response = ApiResponseFactory::error($message, $errors, 400);

        $this->assertFalse($response->success);
        $this->assertEquals($message, $response->message);
        $this->assertEquals($errors, $response->errors);
        $this->assertEquals(400, $response->code);
    }

    /**
     * Тест создания ответа с дефолтным статусом ошибки
     */
    public function testCreateErrorResponseWithDefaultStatus(): void
    {
        $message = 'Error';
        $errors = ['message' => 'Ошибка'];
        $response = ApiResponseFactory::error($message, $errors);

        $this->assertFalse($response->success);
        $this->assertEquals(400, $response->code);
    }

    /**
     * Тест создания ответа с русскими символами
     */
    public function testCreateResponseWithRussianText(): void
    {
        $message = 'Успешно';
        $data = ['сообщение' => 'Успешно'];
        $response = ApiResponseFactory::success($message, $data);

        $this->assertTrue($response->success);
        $this->assertEquals('Успешно', $response->message);
    }

    /**
     * Тест создания ответа с пустыми данными
     */
    public function testCreateResponseWithEmptyData(): void
    {
        $message = 'Success';
        $response = ApiResponseFactory::success($message, []);

        $this->assertTrue($response->success);
        $this->assertEquals([], $response->data);
    }

    /**
     * Тест создания ответа с различными статус кодами
     */
    public function testCreateResponseWithDifferentStatusCodes(): void
    {
        $statusCodes = [200, 201, 400, 401, 403, 404, 500];
        
        foreach ($statusCodes as $statusCode) {
            $response = ApiResponseFactory::success('test', ['data'], $statusCode);
            $this->assertEquals($statusCode, $response->code);
        }
    }
}
