<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Http;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Http\Response;

/**
 * Тесты для Response
 * 
 * @covers \RagSystem\Infrastructure\Http\Response
 */
class ResponseTest extends BaseTestCase
{
    /**
     * Тест создания Response с минимальными параметрами
     */
    public function testResponseCreationWithMinimalParams(): void
    {
        $response = new Response();

        $this->assertEquals('', $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
    }

    /**
     * Тест создания Response со всеми параметрами
     */
    public function testResponseCreationWithAllParams(): void
    {
        $body = 'Test response body';
        $statusCode = 201;
        $headers = ['Content-Type' => 'text/plain'];

        $response = new Response($body, $statusCode, $headers);

        $this->assertEquals($body, $response->getBody());
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
    }

    /**
     * Тест создания JSON ответа
     */
    public function testJsonResponse(): void
    {
        $data = ['message' => 'Success', 'data' => ['id' => 123]];
        $statusCode = 200;

        $response = Response::json($data, $statusCode);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type']);
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($data, $decodedBody);
    }

    /**
     * Тест создания JSON ответа с дефолтным статусом
     */
    public function testJsonResponseWithDefaultStatus(): void
    {
        $data = ['message' => 'Success'];

        $response = Response::json($data);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест создания HTML ответа
     */
    public function testHtmlResponse(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $statusCode = 200;

        $response = Response::html($html, $statusCode);

        $this->assertEquals($html, $response->getBody());
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест создания HTML ответа с дефолтным статусом
     */
    public function testHtmlResponseWithDefaultStatus(): void
    {
        $html = '<html><body><h1>Test</h1></body></html>';

        $response = Response::html($html);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест создания текстового ответа
     */
    public function testTextResponse(): void
    {
        $text = 'Plain text response';
        $statusCode = 200;

        $response = Response::text($text, $statusCode);

        $this->assertEquals($text, $response->getBody());
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест создания текстового ответа с дефолтным статусом
     */
    public function testTextResponseWithDefaultStatus(): void
    {
        $text = 'Plain text response';

        $response = Response::text($text);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест добавления заголовка
     */
    public function testWithHeader(): void
    {
        $response = new Response('test', 200, ['Content-Type' => 'text/plain']);
        
        $newResponse = $response->withHeader('X-Custom-Header', 'custom-value');

        $this->assertEquals('custom-value', $newResponse->getHeaders()['X-Custom-Header']);
        $this->assertEquals('text/plain', $newResponse->getHeaders()['Content-Type']);

        $this->assertArrayNotHasKey('X-Custom-Header', $response->getHeaders());
    }

    /**
     * Тест изменения статуса
     */
    public function testWithStatus(): void
    {
        $response = new Response('test', 200);
        
        $newResponse = $response->withStatus(404);

        $this->assertEquals(404, $newResponse->getStatusCode());
        $this->assertEquals('test', $newResponse->getBody());

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Тест с русскими символами в JSON
     */
    public function testJsonResponseWithRussianText(): void
    {
        $data = ['message' => 'Успешно', 'data' => ['название' => 'Тестовый документ']];

        $response = Response::json($data);

        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals('Успешно', $decodedBody['message']);
        $this->assertEquals('Тестовый документ', $decodedBody['data']['название']);
    }

    /**
     * Тест с русскими символами в HTML
     */
    public function testHtmlResponseWithRussianText(): void
    {
        $html = '<html><body><h1>Привет, мир!</h1></body></html>';

        $response = Response::html($html);

        $this->assertEquals($html, $response->getBody());
        $this->assertEquals('text/html', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест с русскими символами в тексте
     */
    public function testTextResponseWithRussianText(): void
    {
        $text = 'Текстовый ответ на русском языке';

        $response = Response::text($text);

        $this->assertEquals($text, $response->getBody());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    /**
     * Тест с различными статус кодами
     */
    public function testResponseWithDifferentStatusCodes(): void
    {
        $statusCodes = [200, 201, 400, 401, 403, 404, 500];

        foreach ($statusCodes as $statusCode) {
            $response = new Response('test', $statusCode);
            $this->assertEquals($statusCode, $response->getStatusCode());
        }
    }

    /**
     * Тест с пустым телом
     */
    public function testResponseWithEmptyBody(): void
    {
        $response = new Response('', 204);

        $this->assertEquals('', $response->getBody());
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * Тест с множественными заголовками
     */
    public function testResponseWithMultipleHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
            'X-Custom-Header' => 'custom-value'
        ];

        $response = new Response('test', 200, $headers);

        $this->assertEquals($headers, $response->getHeaders());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type']);
        $this->assertEquals('no-cache', $response->getHeaders()['Cache-Control']);
        $this->assertEquals('custom-value', $response->getHeaders()['X-Custom-Header']);
    }

    /**
     * Тест цепочки методов
     */
    public function testResponseMethodChaining(): void
    {
        $response = new Response('test', 200)
            ->withHeader('X-Custom-Header', 'custom-value')
            ->withStatus(201);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('custom-value', $response->getHeaders()['X-Custom-Header']);
        $this->assertEquals('test', $response->getBody());
    }

    /**
     * Тест создания ответа с ошибкой
     */
    public function testErrorResponse(): void
    {
        $message = 'Test error message';
        $statusCode = 500;

        $response = Response::error($message, $statusCode);

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type']);
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals($statusCode, $decodedBody['status']);
    }

    /**
     * Тест создания ответа с ошибкой с дефолтным статусом
     */
    public function testErrorResponseWithDefaultStatus(): void
    {
        $message = 'Test error message';

        $response = Response::error($message);

        $this->assertEquals(500, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals(500, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Не найдено" (404)
     */
    public function testNotFoundResponse(): void
    {
        $message = 'Resource not found';

        $response = Response::notFound($message);

        $this->assertEquals(404, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals(404, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Не найдено" с дефолтным сообщением
     */
    public function testNotFoundResponseWithDefaultMessage(): void
    {
        $response = Response::notFound();

        $this->assertEquals(404, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals('Not Found', $decodedBody['error']);
        $this->assertEquals(404, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Неверный запрос" (400)
     */
    public function testBadRequestResponse(): void
    {
        $message = 'Invalid request data';

        $response = Response::badRequest($message);

        $this->assertEquals(400, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals(400, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Неверный запрос" с дефолтным сообщением
     */
    public function testBadRequestResponseWithDefaultMessage(): void
    {
        $response = Response::badRequest();

        $this->assertEquals(400, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals('Bad Request', $decodedBody['error']);
        $this->assertEquals(400, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Не авторизован" (401)
     */
    public function testUnauthorizedResponse(): void
    {
        $message = 'Authentication required';

        $response = Response::unauthorized($message);

        $this->assertEquals(401, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals(401, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Не авторизован" с дефолтным сообщением
     */
    public function testUnauthorizedResponseWithDefaultMessage(): void
    {
        $response = Response::unauthorized();

        $this->assertEquals(401, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals('Unauthorized', $decodedBody['error']);
        $this->assertEquals(401, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Запрещено" (403)
     */
    public function testForbiddenResponse(): void
    {
        $message = 'Access denied';

        $response = Response::forbidden($message);

        $this->assertEquals(403, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals(403, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Запрещено" с дефолтным сообщением
     */
    public function testForbiddenResponseWithDefaultMessage(): void
    {
        $response = Response::forbidden();

        $this->assertEquals(403, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals('Forbidden', $decodedBody['error']);
        $this->assertEquals(403, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Внутренняя ошибка сервера" (500)
     */
    public function testInternalServerErrorResponse(): void
    {
        $message = 'Server error occurred';

        $response = Response::internalServerError($message);

        $this->assertEquals(500, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($message, $decodedBody['error']);
        $this->assertEquals(500, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Внутренняя ошибка сервера" с дефолтным сообщением
     */
    public function testInternalServerErrorResponseWithDefaultMessage(): void
    {
        $response = Response::internalServerError();

        $this->assertEquals(500, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals('Internal Server Error', $decodedBody['error']);
        $this->assertEquals(500, $decodedBody['status']);
    }

    /**
     * Тест создания ответов с ошибками на русском языке
     */
    public function testErrorResponsesWithRussianText(): void
    {
        $russianMessage = 'Ошибка сервера';

        $response = Response::error($russianMessage, 500);

        $this->assertEquals(500, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($russianMessage, $decodedBody['error']);
        $this->assertEquals(500, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Не найдено" на русском языке
     */
    public function testNotFoundResponseWithRussianText(): void
    {
        $russianMessage = 'Ресурс не найден';

        $response = Response::notFound($russianMessage);

        $this->assertEquals(404, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($russianMessage, $decodedBody['error']);
        $this->assertEquals(404, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Неверный запрос" на русском языке
     */
    public function testBadRequestResponseWithRussianText(): void
    {
        $russianMessage = 'Неверные данные запроса';

        $response = Response::badRequest($russianMessage);

        $this->assertEquals(400, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($russianMessage, $decodedBody['error']);
        $this->assertEquals(400, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Не авторизован" на русском языке
     */
    public function testUnauthorizedResponseWithRussianText(): void
    {
        $russianMessage = 'Требуется авторизация';

        $response = Response::unauthorized($russianMessage);

        $this->assertEquals(401, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($russianMessage, $decodedBody['error']);
        $this->assertEquals(401, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Запрещено" на русском языке
     */
    public function testForbiddenResponseWithRussianText(): void
    {
        $russianMessage = 'Доступ запрещен';

        $response = Response::forbidden($russianMessage);

        $this->assertEquals(403, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($russianMessage, $decodedBody['error']);
        $this->assertEquals(403, $decodedBody['status']);
    }

    /**
     * Тест создания ответа "Внутренняя ошибка сервера" на русском языке
     */
    public function testInternalServerErrorResponseWithRussianText(): void
    {
        $russianMessage = 'Внутренняя ошибка сервера';

        $response = Response::internalServerError($russianMessage);

        $this->assertEquals(500, $response->getStatusCode());
        
        $decodedBody = json_decode($response->getBody(), true);
        $this->assertEquals($russianMessage, $decodedBody['error']);
        $this->assertEquals(500, $decodedBody['status']);
    }

}
