<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Validation\Query;

use PHPUnit\Framework\TestCase;
use RagSystem\Application\DTO\Query\QueryRequestDTO;
use RagSystem\Application\Validation\Query\QueryRequestValidator;
use RagSystem\Application\Validation\ValidationResult;

/**
 * @covers \RagSystem\Application\Validation\Query\QueryRequestValidator
 * @covers \RagSystem\Application\DTO\Query\QueryRequestDTO
 * @covers \RagSystem\Application\Validation\ValidationResult
 */
class QueryRequestValidatorTest extends TestCase
{
    /**
     * Тест валидации корректного QueryRequestDTO
     */
    public function testQueryRequestValidatorValid(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO с временем ответа
     */
    public function testQueryRequestValidatorWithResponseTime(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query', 1.5);
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO с пустым запросом
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
     * Тест валидации QueryRequestDTO с запросом из пробелов
     */
    public function testQueryRequestValidatorWhitespaceQuery(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('   ');
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('query', $result->getErrors());
        $this->assertEquals('Query cannot be empty', $result->getErrors()['query']);
    }

    /**
     * Тест валидации QueryRequestDTO с запросом из табуляции
     */
    public function testQueryRequestValidatorTabQuery(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO("\t");
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('query', $result->getErrors());
        $this->assertEquals('Query cannot be empty', $result->getErrors()['query']);
    }

    /**
     * Тест валидации QueryRequestDTO с запросом из переноса строки
     */
    public function testQueryRequestValidatorNewlineQuery(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO("\n");
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('query', $result->getErrors());
        $this->assertEquals('Query cannot be empty', $result->getErrors()['query']);
    }

    /**
     * Тест валидации QueryRequestDTO с отрицательным временем ответа
     */
    public function testQueryRequestValidatorNegativeResponseTime(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query', -1.0);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('response_time', $result->getErrors());
        $this->assertEquals('Response time must be positive', $result->getErrors()['response_time']);
    }

    /**
     * Тест валидации QueryRequestDTO с нулевым временем ответа
     */
    public function testQueryRequestValidatorZeroResponseTime(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query', 0.0);
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO с null временем ответа
     */
    public function testQueryRequestValidatorNullResponseTime(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query', null);
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO с множественными ошибками
     */
    public function testQueryRequestValidatorMultipleErrors(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('', -1.0);
        
        $result = $validator->validate($dto);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('query', $result->getErrors());
        $this->assertArrayHasKey('response_time', $result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO с неверными данными
     */
    public function testQueryRequestValidatorInvalidData(): void
    {
        $validator = new QueryRequestValidator();
        
        $result = $validator->validate('invalid data');
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('data', $result->getErrors());
        $this->assertEquals('Expected QueryRequestDTO instance', $result->getErrors()['data']);
    }

    /**
     * Тест валидации QueryRequestDTO с пробелами в запросе
     */
    public function testQueryRequestValidatorValidWithSpaces(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('  test query  ');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO со специальными символами
     */
    public function testQueryRequestValidatorValidWithSpecialCharacters(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('test query with special chars: !@#$%^&*()');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест валидации QueryRequestDTO с Unicode символами
     */
    public function testQueryRequestValidatorValidWithUnicode(): void
    {
        $validator = new QueryRequestValidator();
        $dto = new QueryRequestDTO('тестовый запрос на русском языке');
        
        $result = $validator->validate($dto);
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }
}