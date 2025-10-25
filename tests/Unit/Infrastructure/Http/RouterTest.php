<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Http;

use Exception;
use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Http\Router;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use Mockery;

/**
 * Тесты для Router
 * 
 * @covers \RagSystem\Infrastructure\Http\Router
 * @covers \RagSystem\Infrastructure\Http\Request
 * @covers \RagSystem\Infrastructure\Http\Response
 */
class RouterTest extends BaseTestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Mockery::mock(\RagSystem\Infrastructure\DependencyInjection\Container::class);
        $this->router = new Router($container);
    }

    /**
     * Тест добавления GET маршрута
     */
    public function testAddGetRoute(): void
    {
        $path = '/api/documents';
        $handler = function (Request $request) {
            return new Response(json_encode(['status' => 'success']), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $this->router->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Тест добавления POST маршрута
     */
    public function testAddPostRoute(): void
    {
        $path = '/api/documents';
        $handler = function (Request $request) {
            return new Response(json_encode(['status' => 'created']), 201);
        };

        $this->router->post($path, $handler);

        $request = new Request('POST', $path, [], [], [], []);
        $response = $this->router->handle($request);
        
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Тест добавления PUT маршрута
     */
    public function testAddPutRoute(): void
    {
        $path = '/api/documents/{id}';
        $handler = function (Request $request) {
            return new Response(json_encode(['status' => 'updated']), 200);
        };

        $this->router->put($path, $handler);

        $request = new Request('PUT', '/api/documents/123', [], [], [], []);
        $response = $this->router->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Тест добавления DELETE маршрута
     */
    public function testAddDeleteRoute(): void
    {
        $path = '/api/documents/{id}';
        $handler = function (Request $request) {
            return new Response(json_encode(['status' => 'deleted']), 200);
        };

        $this->router->delete($path, $handler);

        $request = new Request('DELETE', '/api/documents/123', [], [], [], []);
        $response = $this->router->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Тест обработки существующего маршрута
     */
    public function testHandleExistingRoute(): void
    {
        $path = '/api/test';
        $expectedResponse = ['status' => 'success', 'message' => 'Test passed'];
        
        $handler = function (Request $request) use ($expectedResponse) {
            return new Response(json_encode($expectedResponse), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedResponse), $response->getBody());
    }

    /**
     * Тест обработки несуществующего маршрута
     */
    public function testHandleNonExistentRoute(): void
    {
        $request = new Request('GET', '/api/non-existent', [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Not Found', $response->getBody());
    }

    /**
     * Тест обработки маршрута с параметрами
     */
    public function testHandleRouteWithParameters(): void
    {
        $path = '/api/documents/{id}';
        $handler = function (Request $request) {
            $id = $request->getPathParam('id');
            return new Response(json_encode(['id' => $id, 'status' => 'found']), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', '/api/documents/123', [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('123', $responseData['id']);
        $this->assertEquals('found', $responseData['status']);
    }

    /**
     * Тест обработки POST запроса с телом
     */
    public function testHandlePostRequestWithBody(): void
    {
        $path = '/api/documents';
        $handler = function (Request $request) {
            $data = $request->getBody();
            return new Response(json_encode(['received' => $data]), 200);
        };

        $this->router->post($path, $handler);

        $body = ['title' => 'Новый документ', 'content' => 'Содержимое'];
        $request = new Request('POST', $path, [], [], $body);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals($body, $responseData['received']);
    }

    /**
     * Тест обработки запроса с заголовками
     */
    public function testHandleRequestWithHeaders(): void
    {
        $path = '/api/test';
        $handler = function (Request $request) {
            $authHeader = $request->getHeader('Authorization');
            return new Response(json_encode(['auth' => $authHeader]), 200);
        };

        $this->router->get($path, $handler);

        $headers = ['authorization' => 'Bearer token123'];
        $request = new Request('GET', $path, $headers, [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('Bearer token123', $responseData['auth']);
    }

    /**
     * Тест обработки запроса с query параметрами
     */
    public function testHandleRequestWithQueryParams(): void
    {
        $path = '/api/search';
        $handler = function (Request $request) {
            $query = $request->getQueryParam('q');
            $limit = $request->getQueryParam('limit');
            return new Response(json_encode(['query' => $query, 'limit' => $limit]), 200);
        };

        $this->router->get($path, $handler);

        $queryParams = ['q' => 'тестовый запрос', 'limit' => '10'];
        $request = new Request('GET', $path, [], $queryParams, [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('тестовый запрос', $responseData['query']);
        $this->assertEquals('10', $responseData['limit']);
    }

    /**
     * Тест обработки ошибки в обработчике
     */
    public function testHandleHandlerError(): void
    {
        $path = '/api/error';
        $handler = function (Request $request) {
            throw new Exception('Handler error');
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Handler error', $response->getBody());
    }

    /**
     * Тест обработки русского текста в маршруте
     */
    public function testHandleRussianText(): void
    {
        $path = '/api/документы';
        $handler = function (Request $request) {
            return new Response(json_encode(['message' => 'Русский текст обработан']), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('Русский текст обработан', $responseData['message']);
    }

    /**
     * Тест добавления PATCH маршрута
     */
    public function testAddPatchRoute(): void
    {
        $path = '/api/documents/{id}';
        $handler = function (Request $request) {
            return new Response(json_encode(['status' => 'updated']), 200);
        };

        $this->router->patch($path, $handler);

        $request = new Request('PATCH', '/api/documents/123', [], [], [], []);
        $response = $this->router->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Тест группировки маршрутов
     */
    public function testGroupRoutes(): void
    {
        $this->router->group('/api/v1', function (Router $router) {
            $router->get('/users', function (Request $request) {
                return new Response(json_encode(['users' => []]), 200);
            });
            $router->post('/users', function (Request $request) {
                return new Response(json_encode(['user' => 'created']), 201);
            });
        });

        $request = new Request('GET', '/api/v1/users', [], [], [], []);
        $response = $this->router->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $request = new Request('POST', '/api/v1/users', [], [], [], []);
        $response = $this->router->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Тест обработки маршрута с несколькими параметрами
     */
    public function testHandleRouteWithMultipleParameters(): void
    {
        $path = '/api/users/{userId}/documents/{docId}';
        $handler = function (Request $request) {
            $userId = $request->getPathParam('userId');
            $docId = $request->getPathParam('docId');
            return new Response(json_encode(['userId' => $userId, 'docId' => $docId]), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', '/api/users/123/documents/456', [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('123', $responseData['userId']);
        $this->assertEquals('456', $responseData['docId']);
    }

    /**
     * Тест обработки маршрута с параметрами в середине пути
     */
    public function testHandleRouteWithMiddleParameters(): void
    {
        $path = '/api/{category}/items/{id}';
        $handler = function (Request $request) {
            $category = $request->getPathParam('category');
            $id = $request->getPathParam('id');
            return new Response(json_encode(['category' => $category, 'id' => $id]), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', '/api/books/items/789', [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('books', $responseData['category']);
        $this->assertEquals('789', $responseData['id']);
    }

    /**
     * Тест обработки маршрута с параметрами в конце пути
     */
    public function testHandleRouteWithEndParameters(): void
    {
        $path = '/api/search/{query}';
        $handler = function (Request $request) {
            $query = $request->getPathParam('query');
            return new Response(json_encode(['query' => $query]), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', '/api/search/тестовый-запрос', [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('тестовый-запрос', $responseData['query']);
    }

    /**
     * Тест обработки маршрута с параметрами и query параметрами
     */
    public function testHandleRouteWithParametersAndQuery(): void
    {
        $path = '/api/documents/{id}';
        $handler = function (Request $request) {
            $id = $request->getPathParam('id');
            $format = $request->getQueryParam('format');
            return new Response(json_encode(['id' => $id, 'format' => $format]), 200);
        };

        $this->router->get($path, $handler);

        $queryParams = ['format' => 'json'];
        $request = new Request('GET', '/api/documents/123', [], $queryParams, [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('123', $responseData['id']);
        $this->assertEquals('json', $responseData['format']);
    }

    /**
     * Тест обработки маршрута с параметрами и заголовками
     */
    public function testHandleRouteWithParametersAndHeaders(): void
    {
        $path = '/api/users/{id}';
        $handler = function (Request $request) {
            $id = $request->getPathParam('id');
            $auth = $request->getHeader('Authorization');
            return new Response(json_encode(['id' => $id, 'auth' => $auth]), 200);
        };

        $this->router->get($path, $handler);

        $headers = ['authorization' => 'Bearer token123'];
        $request = new Request('GET', '/api/users/456', $headers, [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('456', $responseData['id']);
        $this->assertEquals('Bearer token123', $responseData['auth']);
    }

    /**
     * Тест обработки маршрута с параметрами и телом запроса
     */
    public function testHandleRouteWithParametersAndBody(): void
    {
        $path = '/api/documents/{id}';
        $handler = function (Request $request) {
            $id = $request->getPathParam('id');
            $body = $request->getBody();
            return new Response(json_encode(['id' => $id, 'body' => $body]), 200);
        };

        $this->router->put($path, $handler);

        $body = ['title' => 'Обновленный документ', 'content' => 'Новое содержимое'];
        $request = new Request('PUT', '/api/documents/789', [], [], $body);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('789', $responseData['id']);
        $this->assertEquals($body, $responseData['body']);
    }

    /**
     * Тест обработки маршрута с параметрами и файлами
     */
    public function testHandleRouteWithParametersAndFiles(): void
    {
        $path = '/api/upload/{category}';
        $handler = function (Request $request) {
            $category = $request->getPathParam('category');
            $files = $request->getFiles();
            return new Response(json_encode(['category' => $category, 'files' => $files]), 200);
        };

        $this->router->post($path, $handler);

        $files = ['file' => ['name' => 'test.txt', 'type' => 'text/plain']];
        $request = new Request('POST', '/api/upload/documents', [], [], [], $files);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('documents', $responseData['category']);
        $this->assertEquals($files, $responseData['files']);
    }

    /**
     * Тест обработки маршрута с параметрами и всеми типами данных
     */
    public function testHandleRouteWithAllDataTypes(): void
    {
        $path = '/api/complex/{id}';
        $handler = function (Request $request) {
            $id = $request->getPathParam('id');
            $query = $request->getQueryParam('q');
            $header = $request->getHeader('X-Custom');
            $body = $request->getBody();
            $files = $request->getFiles();
            return new Response(json_encode([
                'id' => $id,
                'query' => $query,
                'header' => $header,
                'body' => $body,
                'files' => $files
            ]), 200);
        };

        $this->router->post($path, $handler);

        $headers = ['x-custom' => 'custom-value'];
        $queryParams = ['q' => 'search-term'];
        $body = ['data' => 'test-data'];
        $files = ['file' => ['name' => 'test.txt']];
        $request = new Request('POST', '/api/complex/999', $headers, $queryParams, $body, $files);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('999', $responseData['id']);
        $this->assertEquals('search-term', $responseData['query']);
        $this->assertEquals('custom-value', $responseData['header']);
        $this->assertEquals($body, $responseData['body']);
        $this->assertEquals($files, $responseData['files']);
    }

    /**
     * Тест для покрытия приватного метода addRoute - добавление маршрутов
     */
    public function testAddRouteMethod(): void
    {
        $path = '/api/test-add-route';
        $handler = function (Request $request) {
            return new Response(json_encode(['status' => 'added']), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('added', $responseData['status']);
    }

    /**
     * Тест для покрытия приватного метода matchPattern - сложные паттерны
     */
    public function testMatchPatternWithComplexPatterns(): void
    {
        $path = '/api/{category}/{id}/sub/{subId}';
        $handler = function (Request $request) {
            $category = $request->getPathParam('category');
            $id = $request->getPathParam('id');
            $subId = $request->getPathParam('subId');
            return new Response(json_encode(['category' => $category, 'id' => $id, 'subId' => $subId]), 200);
        };

        $this->router->get($path, $handler);

        $request = new Request('GET', '/api/books/123/sub/456', [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('books', $responseData['category']);
        $this->assertEquals('123', $responseData['id']);
        $this->assertEquals('456', $responseData['subId']);
    }

    /**
     * Тест для покрытия приватного метода callHandler - обработчик массива
     */
    public function testCallHandlerWithArrayHandler(): void
    {
        $path = '/api/test-array-handler';

        $mockController = Mockery::mock();
        $mockController->shouldReceive('testMethod')
            ->once()
            ->with(Mockery::type(Request::class))
            ->andReturn(new Response(json_encode(['status' => 'array-handler']), 200));

        $container = Mockery::mock(\RagSystem\Infrastructure\DependencyInjection\Container::class);
        $container->shouldReceive('get')
            ->with('TestController')
            ->andReturn($mockController);

        $router = new Router($container);
        $router->get($path, ['TestController', 'testMethod']);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('array-handler', $responseData['status']);
    }

    /**
     * Тест для покрытия приватного метода getPatternForHandler - поиск паттерна
     */
    public function testGetPatternForHandler(): void
    {
        $path1 = '/api/test1';
        $path2 = '/api/test2';
        $handler1 = function (Request $request) {
            return new Response(json_encode(['status' => 'handler1']), 200);
        };
        $handler2 = function (Request $request) {
            return new Response(json_encode(['status' => 'handler2']), 200);
        };

        $this->router->get($path1, $handler1);
        $this->router->post($path2, $handler2);

        $request = new Request('GET', $path1, [], [], [], []);
        $response = $this->router->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $request = new Request('POST', $path2, [], [], [], []);
        $response = $this->router->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Тест для покрытия приватного метода callHandler - невалидный обработчик
     */
    public function testCallHandlerWithInvalidHandler(): void
    {
        $path = '/api/invalid-handler';

        $invalidHandler = 'invalid-handler';

        $this->router->get($path, $invalidHandler);

        $request = new Request('GET', $path, [], [], [], []);
        $response = $this->router->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Invalid handler', $response->getBody());
    }
}