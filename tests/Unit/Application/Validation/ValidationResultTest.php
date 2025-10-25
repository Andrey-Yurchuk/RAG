<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Validation;

use PHPUnit\Framework\TestCase;
use RagSystem\Application\Validation\ValidationResult;

/**
 * @covers \RagSystem\Application\Validation\ValidationResult
 */
class ValidationResultTest extends TestCase
{
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

    /**
     * Тест получения первой ошибки из ValidationResult
     */
    public function testValidationResultGetFirstError(): void
    {
        $result = new ValidationResult(['field1' => 'error1', 'field2' => 'error2']);
        
        $this->assertEquals('error1', $result->getFirstError());
    }

    /**
     * Тест получения первой ошибки из пустого ValidationResult
     */
    public function testValidationResultGetFirstErrorEmpty(): void
    {
        $result = new ValidationResult([]);
        
        $this->assertNull($result->getFirstError());
    }

    /**
     * Тест получения ошибки поля из ValidationResult
     */
    public function testValidationResultGetFieldError(): void
    {
        $result = new ValidationResult(['field1' => 'error1', 'field2' => 'error2']);
        
        $this->assertEquals('error1', $result->getFieldError('field1'));
        $this->assertEquals('error2', $result->getFieldError('field2'));
        $this->assertNull($result->getFieldError('field3'));
    }

    /**
     * Тест проверки наличия ошибки поля в ValidationResult
     */
    public function testValidationResultHasFieldError(): void
    {
        $result = new ValidationResult(['field1' => 'error1', 'field2' => 'error2']);
        
        $this->assertTrue($result->hasFieldError('field1'));
        $this->assertTrue($result->hasFieldError('field2'));
        $this->assertFalse($result->hasFieldError('field3'));
    }

    /**
     * Тест добавления множественных ошибок в ValidationResult
     */
    public function testValidationResultAddErrors(): void
    {
        $result = new ValidationResult(['field1' => 'error1']);
        
        $result->addErrors(['field2' => 'error2', 'field3' => 'error3']);
        
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('field1', $result->getErrors());
        $this->assertArrayHasKey('field2', $result->getErrors());
        $this->assertArrayHasKey('field3', $result->getErrors());
        $this->assertEquals('error1', $result->getErrors()['field1']);
        $this->assertEquals('error2', $result->getErrors()['field2']);
        $this->assertEquals('error3', $result->getErrors()['field3']);
    }

    /**
     * Тест добавления ошибок с перезаписью в ValidationResult
     */
    public function testValidationResultAddErrorsOverwrite(): void
    {
        $result = new ValidationResult(['field1' => 'error1']);
        
        $result->addErrors(['field1' => 'new_error1', 'field2' => 'error2']);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals('new_error1', $result->getErrors()['field1']);
        $this->assertEquals('error2', $result->getErrors()['field2']);
    }

    /**
     * Тест конструктора ValidationResult с ошибками
     */
    public function testValidationResultConstructorWithErrors(): void
    {
        $errors = ['field1' => 'error1', 'field2' => 'error2'];
        $result = new ValidationResult($errors);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals($errors, $result->getErrors());
    }

    /**
     * Тест конструктора ValidationResult без параметров
     */
    public function testValidationResultConstructorEmpty(): void
    {
        $result = new ValidationResult();
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    /**
     * Тест ValidationResult с множественными ошибками
     */
    public function testValidationResultMultipleErrors(): void
    {
        $result = new ValidationResult();
        
        $result->addError('field1', 'error1');
        $result->addError('field2', 'error2');
        $result->addError('field3', 'error3');
        
        $this->assertFalse($result->isValid());
        $this->assertCount(3, $result->getErrors());
        $this->assertEquals('error1', $result->getFieldError('field1'));
        $this->assertEquals('error2', $result->getFieldError('field2'));
        $this->assertEquals('error3', $result->getFieldError('field3'));
    }
}