<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\Database\Types;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\Database\Types\VectorType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PDO;
use Mockery;

/**
 * Тесты для VectorType
 * 
 * @covers \RagSystem\Infrastructure\Database\Types\VectorType
 */
class VectorTypeTest extends BaseTestCase
{
    private VectorType $vectorType;
    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->vectorType = new VectorType();
        $this->platform = Mockery::mock(AbstractPlatform::class);
    }

    /**
     * Тест получения SQL декларации
     */
    public function testGetSQLDeclaration(): void
    {
        $column = ['name' => 'embedding', 'type' => 'vector'];
        
        $result = $this->vectorType->getSQLDeclaration($column, $this->platform);
        
        $this->assertEquals('vector', $result);
    }

    /**
     * Тест получения имени типа
     */
    public function testGetName(): void
    {
        $result = $this->vectorType->getName();
        
        $this->assertEquals('vector', $result);
    }

    /**
     * Тест получения типа привязки PDO
     */
    public function testGetBindingType(): void
    {
        $result = $this->vectorType->getBindingType();
        
        $this->assertEquals(PDO::PARAM_STR, $result);
    }

    /**
     * Тест получения маппированных типов базы данных
     */
    public function testGetMappedDatabaseTypes(): void
    {
        $result = $this->vectorType->getMappedDatabaseTypes($this->platform);
        
        $this->assertEquals(['vector'], $result);
    }

    /**
     * Тест с различными колонками
     */
    public function testGetSQLDeclarationWithDifferentColumns(): void
    {
        $column1 = ['name' => 'embedding1', 'type' => 'vector'];
        $column2 = ['name' => 'embedding2', 'type' => 'vector', 'length' => 512];
        
        $result1 = $this->vectorType->getSQLDeclaration($column1, $this->platform);
        $result2 = $this->vectorType->getSQLDeclaration($column2, $this->platform);
        
        $this->assertEquals('vector', $result1);
        $this->assertEquals('vector', $result2);
    }

    /**
     * Тест константы VECTOR
     */
    public function testVectorConstant(): void
    {
        $this->assertEquals('vector', VectorType::VECTOR);
    }

    /**
     * Тест с русскими символами
     */
    public function testWithRussianText(): void
    {
        $column = ['name' => 'вектор', 'type' => 'vector'];
        
        $result = $this->vectorType->getSQLDeclaration($column, $this->platform);
        
        $this->assertEquals('vector', $result);
    }
}
