<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Http;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Http\Request;

/**
 * Тесты для Request
 * 
 * @covers \RagSystem\Infrastructure\Http\Request
 */
class RequestTest extends BaseTestCase
{
    /**
     * Тест создания Request с минимальными параметрами
     */
    public function testRequestCreationWithMinimalParams(): void
    {
        $request = new Request('GET', '/test');

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getUri());
        $this->assertEmpty($request->getHeaders());
        $this->assertEmpty($request->getQuery());
        $this->assertEmpty($request->getBody());
        $this->assertEmpty($request->getFiles());
        $this->assertEmpty($request->getPathParams());
    }

    /**
     * Тест создания Request со всеми параметрами
     */
    public function testRequestCreationWithAllParams(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query = ['page' => '1'];
        $body = ['data' => 'test'];
        $files = ['file' => ['name' => 'test.txt']];
        $pathParams = ['id' => '123'];

        $request = new Request('POST', '/api/test', $headers, $query, $body, $files, $pathParams);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/api/test', $request->getUri());
        $this->assertEquals($headers, $request->getHeaders());
        $this->assertEquals($query, $request->getQuery());
        $this->assertEquals($body, $request->getBody());
        $this->assertEquals($files, $request->getFiles());
        $this->assertEquals($pathParams, $request->getPathParams());
    }

    /**
     * Тест автоматического преобразования метода в верхний регистр
     */
    public function testMethodUppercaseConversion(): void
    {
        $request = new Request('post', '/test');
        $this->assertEquals('POST', $request->getMethod());

        $request = new Request('put', '/test');
        $this->assertEquals('PUT', $request->getMethod());

        $request = new Request('delete', '/test');
        $this->assertEquals('DELETE', $request->getMethod());
    }

    /**
     * Тест получения заголовка
     */
    public function testGetHeader(): void
    {
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer token'];
        $request = new Request('GET', '/test', $headers);

        $this->assertEquals('application/json', $request->getHeader('Content-Type'));
        $this->assertEquals('Bearer token', $request->getHeader('Authorization'));
        $this->assertNull($request->getHeader('Non-Existent'));
    }

    /**
     * Тест получения заголовка без учета регистра
     */
    public function testGetHeaderCaseInsensitive(): void
    {
        $headers = ['content-type' => 'application/json'];
        $request = new Request('GET', '/test', $headers);

        $this->assertEquals('application/json', $request->getHeader('Content-Type'));
        $this->assertEquals('application/json', $request->getHeader('CONTENT-TYPE'));
        $this->assertEquals('application/json', $request->getHeader('content-type'));
    }

    /**
     * Тест получения параметра запроса
     */
    public function testGetQueryParam(): void
    {
        $query = ['page' => '1', 'limit' => '10'];
        $request = new Request('GET', '/test', [], $query);

        $this->assertEquals('1', $request->getQueryParam('page'));
        $this->assertEquals('10', $request->getQueryParam('limit'));
        $this->assertNull($request->getQueryParam('non-existent'));
    }

    /**
     * Тест получения параметра тела запроса
     */
    public function testGetBodyParam(): void
    {
        $body = ['name' => 'John', 'age' => 30];
        $request = new Request('POST', '/test', [], [], $body);

        $this->assertEquals('John', $request->getBodyParam('name'));
        $this->assertEquals(30, $request->getBodyParam('age'));
        $this->assertNull($request->getBodyParam('non-existent'));
    }

    /**
     * Тест получения файла
     */
    public function testGetFile(): void
    {
        $files = [
            'upload' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 1024,
                'tmp_name' => '/tmp/upload',
                'error' => 0
            ]
        ];
        $request = new Request('POST', '/upload', [], [], [], $files);

        $file = $request->getFile('upload');
        $this->assertIsArray($file);
        $this->assertEquals('test.txt', $file['name']);
        $this->assertEquals('text/plain', $file['type']);
        $this->assertEquals(1024, $file['size']);

        $this->assertNull($request->getFile('non-existent'));
    }

    /**
     * Тест проверки JSON запроса
     */
    public function testIsJson(): void
    {
        $jsonHeaders = ['Content-Type' => 'application/json'];
        $request = new Request('POST', '/test', $jsonHeaders);
        $this->assertTrue($request->isJson());

        $xmlHeaders = ['Content-Type' => 'application/xml'];
        $request = new Request('POST', '/test', $xmlHeaders);
        $this->assertFalse($request->isJson());

        $noHeaders = [];
        $request = new Request('POST', '/test', $noHeaders);
        $this->assertFalse($request->isJson());
    }

    /**
     * Тест проверки метода POST
     */
    public function testIsPost(): void
    {
        $request = new Request('POST', '/test');
        $this->assertTrue($request->isPost());

        $request = new Request('GET', '/test');
        $this->assertFalse($request->isPost());
    }

    /**
     * Тест проверки метода GET
     */
    public function testIsGet(): void
    {
        $request = new Request('GET', '/test');
        $this->assertTrue($request->isGet());

        $request = new Request('POST', '/test');
        $this->assertFalse($request->isGet());
    }

    /**
     * Тест проверки метода PUT
     */
    public function testIsPut(): void
    {
        $request = new Request('PUT', '/test');
        $this->assertTrue($request->isPut());

        $request = new Request('GET', '/test');
        $this->assertFalse($request->isPut());
    }

    /**
     * Тест проверки метода DELETE
     */
    public function testIsDelete(): void
    {
        $request = new Request('DELETE', '/test');
        $this->assertTrue($request->isDelete());

        $request = new Request('GET', '/test');
        $this->assertFalse($request->isDelete());
    }

    /**
     * Тест получения параметра пути
     */
    public function testGetPathParam(): void
    {
        $pathParams = ['id' => '123', 'slug' => 'test-page'];
        $request = new Request('GET', '/test', [], [], [], [], $pathParams);

        $this->assertEquals('123', $request->getPathParam('id'));
        $this->assertEquals('test-page', $request->getPathParam('slug'));
        $this->assertNull($request->getPathParam('non-existent'));
    }

    /**
     * Тест добавления параметров пути
     */
    public function testWithAddedPathParams(): void
    {
        $originalPathParams = ['id' => '123'];
        $request = new Request('GET', '/test', [], [], [], [], $originalPathParams);

        $newPathParams = ['slug' => 'test-page'];
        $newRequest = $request->withAddedPathParams($newPathParams);

        $this->assertNull($newRequest->getPathParam('id'));
        $this->assertEquals('test-page', $newRequest->getPathParam('slug'));

        $this->assertEquals('123', $request->getPathParam('id'));
        $this->assertNull($request->getPathParam('slug'));
    }

    /**
     * Тест с русскими символами в параметрах
     */
    public function testRequestWithRussianText(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query = ['search' => 'поиск'];
        $body = ['title' => 'Заголовок на русском'];
        $pathParams = ['category' => 'категория'];

        $request = new Request('POST', '/api/search', $headers, $query, $body, [], $pathParams);

        $this->assertEquals('поиск', $request->getQueryParam('search'));
        $this->assertEquals('Заголовок на русском', $request->getBodyParam('title'));
        $this->assertEquals('категория', $request->getPathParam('category'));
    }

    /**
     * Тест с пустыми значениями
     */
    public function testRequestWithEmptyValues(): void
    {
        $request = new Request('GET', '/test');

        $this->assertEmpty($request->getHeaders());
        $this->assertEmpty($request->getQuery());
        $this->assertEmpty($request->getBody());
        $this->assertEmpty($request->getFiles());
        $this->assertEmpty($request->getPathParams());
    }

    /**
     * Тест с null значениями в параметрах
     */
    public function testRequestWithNullValues(): void
    {
        $body = ['field1' => null, 'field2' => 'value'];
        $request = new Request('POST', '/test', [], [], $body);

        $this->assertNull($request->getBodyParam('field1'));
        $this->assertEquals('value', $request->getBodyParam('field2'));
    }

    /**
     * Тест создания Request из глобальных переменных
     */
    public function testFromGlobals(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalFiles = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_GET = ['page' => '1'];
        $_POST = ['data' => 'test'];
        $_FILES = ['file' => ['name' => 'test.txt']];

        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        $request = Request::fromGlobals();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/api/test', $request->getUri());
        $this->assertEquals(['page' => '1'], $request->getQuery());
        $this->assertEquals(['data' => 'test'], $request->getBody());
        $this->assertEquals(['file' => ['name' => 'test.txt']], $request->getFiles());

        $_SERVER = $originalServer;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_FILES = $originalFiles;
    }

    /**
     * Тест создания Request из глобальных переменных с JSON
     */
    public function testFromGlobalsWithJson(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalFiles = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/test';
        $_GET = ['page' => '1'];
        $_POST = []; // Пустой POST для JSON
        $_FILES = [];

        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';

        $request = Request::fromGlobals();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/api/test', $request->getUri());
        $this->assertEquals(['page' => '1'], $request->getQuery());
        $this->assertTrue($request->isJson());

        $_SERVER = $originalServer;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_FILES = $originalFiles;
    }

    /**
     * Тест создания Request с параметрами пути
     */
    public function testWithPathParams(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalFiles = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users/123';
        $_GET = ['page' => '1'];
        $_POST = [];
        $_FILES = [];

        if (!function_exists('getallheaders')) {
            function getallheaders() {
                return [];
            }
        }

        $pathParams = ['id' => '123', 'slug' => 'test-page'];
        $request = Request::withPathParams($pathParams);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/api/users/123', $request->getUri());
        $this->assertEquals(['page' => '1'], $request->getQuery());
        $this->assertEquals($pathParams, $request->getPathParams());
        $this->assertEquals('123', $request->getPathParam('id'));
        $this->assertEquals('test-page', $request->getPathParam('slug'));

        $_SERVER = $originalServer;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_FILES = $originalFiles;
    }

    /**
     * Тест создания Request из глобальных переменных с дефолтными значениями
     */
    public function testFromGlobalsWithDefaults(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalFiles = $_FILES;

        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];

        if (!function_exists('getallheaders')) {
            function getallheaders() {
                return [];
            }
        }

        $request = Request::fromGlobals();

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/', $request->getUri());
        $this->assertEmpty($request->getQuery());
        $this->assertEmpty($request->getBody());
        $this->assertEmpty($request->getFiles());

        $_SERVER = $originalServer;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_FILES = $originalFiles;
    }

    /**
     * Тест создания Request из глобальных переменных с русскими символами
     */
    public function testFromGlobalsWithRussianText(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalFiles = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/поиск';
        $_GET = ['search' => 'поиск'];
        $_POST = ['title' => 'Заголовок на русском'];
        $_FILES = [];

        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        $request = Request::fromGlobals();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/api/поиск', $request->getUri());
        $this->assertEquals('поиск', $request->getQueryParam('search'));
        $this->assertEquals('Заголовок на русском', $request->getBodyParam('title'));

        $_SERVER = $originalServer;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_FILES = $originalFiles;
    }

    /**
     * Тест создания Request из глобальных переменных с файлами
     */
    public function testFromGlobalsWithFiles(): void
    {
        $originalServer = $_SERVER;
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalFiles = $_FILES;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/upload';
        $_GET = [];
        $_POST = [];
        $_FILES = [
            'file' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'size' => 1024,
                'tmp_name' => '/tmp/upload',
                'error' => 0
            ]
        ];

        $_SERVER['HTTP_CONTENT_TYPE'] = 'multipart/form-data';

        $request = Request::fromGlobals();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/api/upload', $request->getUri());
        $this->assertEmpty($request->getQuery());
        $this->assertEmpty($request->getBody());
        
        $file = $request->getFile('file');
        $this->assertIsArray($file);
        $this->assertEquals('test.txt', $file['name']);
        $this->assertEquals('text/plain', $file['type']);
        $this->assertEquals(1024, $file['size']);

        $_SERVER = $originalServer;
        $_GET = $originalGet;
        $_POST = $originalPost;
        $_FILES = $originalFiles;
    }
}
