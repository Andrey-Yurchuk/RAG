<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Domain\Model;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Tests\Helpers\TestDataFactory;
use RagSystem\Domain\Model\Query;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Тесты для модели Query
 * 
 * @covers \RagSystem\Domain\Model\Query
 */
class QueryTest extends BaseTestCase
{
    /**
     * Тест создания запроса через конструктор
     */
    public function testQueryCreation(): void
    {
        $queryText = 'О чем этот документ?';
        $queryEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];
        $response = 'Этот документ о тестировании системы RAG';
        $responseTime = 1.5;

        $query = new Query($queryText, $queryEmbedding, $response, $responseTime);

        $this->assertEquals($queryText, $query->getQueryText());
        $this->assertEquals($queryEmbedding, $query->getQueryEmbedding());
        $this->assertEquals($response, $query->getResponse());
        $this->assertEquals($responseTime, $query->getResponseTime());
        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $query->getId());
        $this->assertInstanceOf(DateTimeImmutable::class, $query->getCreatedAt());
    }

    /**
     * Тест создания запроса с минимальными параметрами
     */
    public function testQueryCreationWithMinimalParameters(): void
    {
        $queryText = 'Простой запрос';

        $query = new Query($queryText);

        $this->assertEquals($queryText, $query->getQueryText());
        $this->assertNull($query->getQueryEmbedding());
        $this->assertNull($query->getResponse());
        $this->assertNull($query->getResponseTime());
        $this->assertFalse($query->hasEmbedding());
        $this->assertFalse($query->hasResponse());
    }

    /**
     * Тест создания запроса из массива
     */
    public function testQueryFromArray(): void
    {
        $data = TestDataFactory::createQuery([
            'id' => '550e8400-e29b-41d4-a716-446655440002',
            'query_text' => 'Запрос из массива',
            'query_embedding' => [0.2, 0.3, 0.4, 0.5, 0.6],
            'response' => 'Ответ из массива',
            'response_time' => 2.5,
            'created_at' => '2025-01-17 11:00:00',
        ]);

        $query = Query::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440002', $query->getId()->toString());
        $this->assertEquals('Запрос из массива', $query->getQueryText());
        $this->assertEquals([0.2, 0.3, 0.4, 0.5, 0.6], $query->getQueryEmbedding());
        $this->assertEquals('Ответ из массива', $query->getResponse());
        $this->assertEquals(2.5, $query->getResponseTime());
        $this->assertEquals('2025-01-17 11:00:00', $query->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Тест создания запроса из массива с минимальными данными
     */
    public function testQueryFromArrayWithMinimalData(): void
    {
        $data = [
            'query_text' => 'Минимальный запрос',
        ];

        $query = Query::fromArray($data);

        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $query->getId());
        $this->assertEquals('Минимальный запрос', $query->getQueryText());
        $this->assertNull($query->getQueryEmbedding());
        $this->assertNull($query->getResponse());
        $this->assertNull($query->getResponseTime());
        $this->assertInstanceOf(DateTimeImmutable::class, $query->getCreatedAt());
    }

    /**
     * Тест создания запроса из массива с альтернативными ключами
     */
    public function testQueryFromArrayWithAlternativeKeys(): void
    {
        $data = [
            'queryText' => 'Запрос с альтернативными ключами',
            'queryEmbedding' => [0.1, 0.2, 0.3],
            'response' => 'Ответ с альтернативными ключами',
            'responseTime' => 1.2,
            'createdAt' => '2025-01-17 12:00:00',
        ];

        $query = Query::fromArray($data);

        $this->assertEquals('Запрос с альтернативными ключами', $query->getQueryText());
        $this->assertEquals([0.1, 0.2, 0.3], $query->getQueryEmbedding());
        $this->assertEquals('Ответ с альтернативными ключами', $query->getResponse());
        $this->assertEquals(1.2, $query->getResponseTime());
        $this->assertEquals('2025-01-17 12:00:00', $query->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Тест установки эмбеддинга запроса
     */
    public function testSetQueryEmbedding(): void
    {
        $query = new Query('Запрос для установки эмбеддинга');

        $this->assertFalse($query->hasEmbedding());

        $embedding = [0.1, 0.2, 0.3, 0.4, 0.5];
        $query->setQueryEmbedding($embedding);

        $this->assertEquals($embedding, $query->getQueryEmbedding());
        $this->assertTrue($query->hasEmbedding());
    }

    /**
     * Тест установки ответа
     */
    public function testSetResponse(): void
    {
        $query = new Query('Запрос для установки ответа');

        $this->assertFalse($query->hasResponse());

        $response = 'Это ответ на запрос';
        $query->setResponse($response);

        $this->assertEquals($response, $query->getResponse());
        $this->assertTrue($query->hasResponse());
    }

    /**
     * Тест установки времени ответа
     */
    public function testSetResponseTime(): void
    {
        $query = new Query('Запрос для установки времени ответа');

        $this->assertNull($query->getResponseTime());

        $responseTime = 3.5;
        $query->setResponseTime($responseTime);

        $this->assertEquals($responseTime, $query->getResponseTime());
    }

    /**
     * Тест удаления эмбеддинга
     */
    public function testRemoveEmbedding(): void
    {
        $embedding = [0.1, 0.2, 0.3];
        $query = new Query('Запрос с эмбеддингом', $embedding);

        $this->assertTrue($query->hasEmbedding());

        $query->setQueryEmbedding(null);

        $this->assertNull($query->getQueryEmbedding());
        $this->assertFalse($query->hasEmbedding());
    }

    /**
     * Тест удаления ответа
     */
    public function testRemoveResponse(): void
    {
        $response = 'Ответ на запрос';
        $query = new Query('Запрос с ответом', null, $response);

        $this->assertTrue($query->hasResponse());

        $query->setResponse(null);

        $this->assertNull($query->getResponse());
        $this->assertFalse($query->hasResponse());
    }

    /**
     * Тест удаления времени ответа
     */
    public function testRemoveResponseTime(): void
    {
        $responseTime = 2.5;
        $query = new Query('Запрос с временем ответа', null, null, $responseTime);

        $this->assertEquals($responseTime, $query->getResponseTime());

        $query->setResponseTime(null);

        $this->assertNull($query->getResponseTime());
    }

    /**
     * Тест преобразования запроса в массив
     */
    public function testToArray(): void
    {
        $queryText = 'Запрос для массива';
        $queryEmbedding = [0.1, 0.2, 0.3, 0.4, 0.5];
        $response = 'Ответ для массива';
        $responseTime = 1.8;

        $query = new Query($queryText, $queryEmbedding, $response, $responseTime);
        $array = $query->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('queryText', $array);
        $this->assertArrayHasKey('queryEmbedding', $array);
        $this->assertArrayHasKey('response', $array);
        $this->assertArrayHasKey('responseTime', $array);
        $this->assertArrayHasKey('createdAt', $array);

        $this->assertEquals($queryText, $array['queryText']);
        $this->assertEquals($queryEmbedding, $array['queryEmbedding']);
        $this->assertEquals($response, $array['response']);
        $this->assertEquals($responseTime, $array['responseTime']);
        $this->assertIsString($array['id']);
        $this->assertIsString($array['createdAt']);
    }

    /**
     * Тест преобразования запроса в массив с null значениями
     */
    public function testToArrayWithNullValues(): void
    {
        $query = new Query('Запрос без дополнительных данных');
        $array = $query->toArray();

        $this->assertNull($array['queryEmbedding']);
        $this->assertNull($array['response']);
        $this->assertNull($array['responseTime']);
    }

    /**
     * Тест неизменяемости ID запроса
     */
    public function testQueryIdImmutability(): void
    {
        $query = new Query('Запрос для тестирования неизменяемости');
        $originalId = $query->getId();

        $query->setQueryEmbedding([0.1, 0.2, 0.3]);
        $query->setResponse('Новый ответ');
        $query->setResponseTime(2.0);

        $this->assertEquals($originalId, $query->getId());
    }

    /**
     * Тест неизменяемости времени создания
     */
    public function testCreatedAtImmutability(): void
    {
        $query = new Query('Запрос для тестирования времени создания');
        $originalCreatedAt = $query->getCreatedAt();

        $query->setQueryEmbedding([0.1, 0.2, 0.3]);
        $query->setResponse('Новый ответ');
        $query->setResponseTime(2.0);

        $this->assertEquals($originalCreatedAt, $query->getCreatedAt());
    }

    /**
     * Тест валидации данных при создании из массива
     */
    public function testFromArrayDataValidation(): void
    {
        $data = [
            'id' => 'invalid-uuid',
            'query_text' => 'Запрос с невалидным UUID',
        ];

        $this->expectException(\InvalidArgumentException::class);
        Query::fromArray($data);
    }

    /**
     * Тест валидации времени при создании из массива
     */
    public function testFromArrayInvalidDateTime(): void
    {
        $data = [
            'query_text' => 'Запрос с невалидным временем',
            'created_at' => 'invalid-date',
        ];

        $this->expectException(\Exception::class);
        Query::fromArray($data);
    }

    /**
     * Тест работы с различными типами времени ответа
     */
    public function testDifferentResponseTimes(): void
    {
        $query = new Query('Запрос для тестирования времени ответа');

        $times = [0.1, 1.0, 5.5, 10.0, 60.0, 120.5];
        
        foreach ($times as $time) {
            $query->setResponseTime($time);
            $this->assertEquals($time, $query->getResponseTime());
        }

        $query->setResponseTime(0.0);
        $this->assertEquals(0.0, $query->getResponseTime());
    }

    /**
     * Тест работы с различными типами эмбеддингов
     */
    public function testDifferentEmbeddingTypes(): void
    {
        $query = new Query('Запрос для тестирования эмбеддингов');

        // Тест с пустым массивом
        $query->setQueryEmbedding([]);
        $this->assertTrue($query->hasEmbedding());
        $this->assertEquals([], $query->getQueryEmbedding());

        // Тест с большим эмбеддингом
        $largeEmbedding = array_fill(0, 384, 0.5);
        $query->setQueryEmbedding($largeEmbedding);
        $this->assertTrue($query->hasEmbedding());
        $this->assertCount(384, $query->getQueryEmbedding());

        // Тест с отрицательными значениями
        $negativeEmbedding = [-0.1, -0.2, -0.3];
        $query->setQueryEmbedding($negativeEmbedding);
        $this->assertTrue($query->hasEmbedding());
        $this->assertEquals($negativeEmbedding, $query->getQueryEmbedding());
    }

    /**
     * Тест работы с различными типами ответов
     */
    public function testDifferentResponseTypes(): void
    {
        $query = new Query('Запрос для тестирования ответов');

        // Тест с пустой строкой
        $query->setResponse('');
        $this->assertTrue($query->hasResponse());
        $this->assertEquals('', $query->getResponse());

        // Тест с длинным ответом
        $longResponse = str_repeat('Это очень длинный ответ. ', 100);
        $query->setResponse($longResponse);
        $this->assertTrue($query->hasResponse());
        $this->assertEquals($longResponse, $query->getResponse());

        // Тест с ответом на русском языке
        $russianResponse = 'Это ответ на русском языке с различными символами: !@#$%^&*()';
        $query->setResponse($russianResponse);
        $this->assertTrue($query->hasResponse());
        $this->assertEquals($russianResponse, $query->getResponse());
    }
}
