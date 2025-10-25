<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\DependencyInjection;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Infrastructure\DependencyInjection\Container;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\TestClass;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\DependencyClass;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\ServiceWithDependency;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\ServiceWithDefaults;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\ServiceWithUnresolvable;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\ServiceWithUnion;
use RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses\AbstractClass;
use InvalidArgumentException;
use Mockery;
use stdClass;

/**
 * @covers \RagSystem\Infrastructure\DependencyInjection\Container
 */
class ContainerTest extends BaseTestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = new Container();
    }

    /**
     * Тест привязки сервиса
     */
    public function testBind(): void
    {
        $this->container->bind('TestInterface', 'TestClass');
        
        $this->assertTrue($this->container->has('TestInterface'));
    }

    /**
     * Тест привязки сервиса без конкретной реализации
     */
    public function testBindWithoutConcrete(): void
    {
        $this->container->bind('TestClass');
        
        $this->assertTrue($this->container->has('TestClass'));
    }

    /**
     * Тест регистрации синглтона
     */
    public function testSingleton(): void
    {
        $this->container->singleton('TestInterface', 'TestClass');
        
        $this->assertTrue($this->container->has('TestInterface'));
    }

    /**
     * Тест регистрации готового экземпляра
     */
    public function testInstance(): void
    {
        $instance = new stdClass();
        $this->container->instance('TestInterface', $instance);

        $this->assertSame($instance, $this->container->get('TestInterface'));
    }

    /**
     * Тест получения сервиса по ID
     */
    public function testGet(): void
    {
        $this->container->bind('TestClass', TestClass::class);
        
        $result = $this->container->get('TestClass');
        
        $this->assertInstanceOf(TestClass::class, $result);
    }

    /**
     * Тест получения сервиса с callable
     */
    public function testGetWithCallable(): void
    {
        $this->container->bind('TestClass', function() {
            return new TestClass();
        });
        
        $result = $this->container->get('TestClass');
        
        $this->assertInstanceOf(TestClass::class, $result);
    }

    /**
     * Тест получения несуществующего сервиса
     */
    public function testGetNonExistentService(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service NonExistentClass not found');
        
        $this->container->get('NonExistentClass');
    }

    /**
     * Тест проверки наличия сервиса
     */
    public function testHas(): void
    {
        $this->container->bind('TestInterface', 'TestClass');
        
        $this->assertTrue($this->container->has('TestInterface'));
        $this->assertFalse($this->container->has('NonExistentInterface'));
    }

    /**
     * Тест проверки наличия существующего класса
     */
    public function testHasExistingClass(): void
    {
        $this->assertTrue($this->container->has(stdClass::class));
        $this->assertTrue($this->container->has(\DateTime::class));
    }

    /**
     * Тест создания экземпляра без конструктора
     */
    public function testBuildWithoutConstructor(): void
    {
        $this->container->bind(stdClass::class);
        
        $result = $this->container->get(stdClass::class);
        
        $this->assertInstanceOf(stdClass::class, $result);
    }

    /**
     * Тест создания экземпляра с конструктором без параметров
     */
    public function testBuildWithEmptyConstructor(): void
    {
        $this->container->bind(stdClass::class);
        
        $result = $this->container->get(stdClass::class);
        
        $this->assertInstanceOf(stdClass::class, $result);
    }

    /**
     * Тест создания экземпляра с зависимостями
     */
    public function testBuildWithDependencies(): void
    {
        $this->container->bind(DependencyClass::class);
        $this->container->bind(ServiceWithDependency::class);
        
        $result = $this->container->get(ServiceWithDependency::class);
        
        $this->assertInstanceOf(ServiceWithDependency::class, $result);
    }

    /**
     * Тест создания экземпляра с параметрами по умолчанию
     */
    public function testBuildWithDefaultParameters(): void
    {
        $this->container->bind('ServiceWithDefaults', ServiceWithDefaults::class);
        
        $result = $this->container->get('ServiceWithDefaults');
        
        $this->assertInstanceOf(ServiceWithDefaults::class, $result);
    }

    /**
     * Тест создания экземпляра с неразрешимыми параметрами
     */
    public function testBuildWithUnresolvableParameters(): void
    {
        $this->container->bind('ServiceWithUnresolvable', ServiceWithUnresolvable::class);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve parameter');
        
        $this->container->get('ServiceWithUnresolvable');
    }

    /**
     * Тест создания экземпляра с union типами
     */
    public function testBuildWithUnionTypes(): void
    {
        $this->container->bind('ServiceWithUnion', ServiceWithUnion::class);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve union or intersection type');
        
        $this->container->get('ServiceWithUnion');
    }

    /**
     * Тест создания экземпляра с неинстанцируемым классом
     */
    public function testBuildWithNonInstantiableClass(): void
    {
        $this->container->bind('AbstractClass', AbstractClass::class);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not instantiable');
        
        $this->container->get('AbstractClass');
    }

    /**
     * Тест синглтон поведения
     */
    public function testSingletonBehavior(): void
    {
        $this->container->singleton('TestClass', TestClass::class);
        
        $instance1 = $this->container->get('TestClass');
        $instance2 = $this->container->get('TestClass');
        
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Тест с русскими символами в именах сервисов
     */
    public function testWithRussianServiceNames(): void
    {
        $this->container->bind('СервисТест', TestClass::class);
        
        $this->assertTrue($this->container->has('СервисТест'));
    }

    /**
     * Тест с пустыми именами сервисов
     */
    public function testWithEmptyServiceNames(): void
    {
        $this->container->bind('', TestClass::class);
        
        $this->assertTrue($this->container->has(''));
    }

    /**
     * Тест с null значениями
     */
    public function testWithNullValues(): void
    {
        $this->container->bind('NullService', function() { return null; });
        
        $this->assertNull($this->container->get('NullService'));
    }

    /**
     * Тест с массивом в качестве сервиса
     */
    public function testWithArrayService(): void
    {
        $array = ['test' => 'value'];
        $this->container->instance('ArrayService', $array);
        
        $result = $this->container->get('ArrayService');
        
        $this->assertEquals($array, $result);
    }

    /**
     * Тест с числовыми значениями
     */
    public function testWithNumericValues(): void
    {
        $this->container->instance('NumberService', 42);
        
        $result = $this->container->get('NumberService');
        
        $this->assertEquals(42, $result);
    }

    /**
     * Тест с булевыми значениями
     */
    public function testWithBooleanValues(): void
    {
        $this->container->instance('BooleanService', true);
        
        $result = $this->container->get('BooleanService');
        
        $this->assertTrue($result);
    }
}