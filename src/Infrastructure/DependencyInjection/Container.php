<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\DependencyInjection;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    public function __construct(
        private array $bindings = [],
        private array $instances = []
    ) {
    }

    /**
     * Регистрирует привязку абстракции к конкретной реализации
     */
    public function bind(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    /**
     * Регистрирует синглтон привязку абстракции к конкретной реализации
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
    }

    /**
     * Регистрирует готовый экземпляр для абстракции
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Получает экземпляр сервиса по id
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new InvalidArgumentException("Service {$id} not found");
        }

        $concrete = $this->bindings[$id];

        if (is_callable($concrete)) {
            $instance = $concrete($this);
        } else {
            $instance = $this->build($concrete);
        }

        $this->instances[$id] = $instance;
        return $instance;
    }

    /**
     * Проверяет наличие сервиса в контейнере
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || class_exists($id);
    }

    /**
     * Создает экземпляр класса с автоматическим разрешением зависимостей
     */
    private function build(string $class)
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || ($type instanceof \ReflectionNamedType && $type->isBuiltin())) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException("Cannot resolve parameter {$parameter->getName()}");
                }
            } else {
                if ($type instanceof \ReflectionNamedType) {
                    $dependencies[] = $this->get($type->getName());
                } else {
                    throw new InvalidArgumentException(
                        "Cannot resolve union or intersection type for parameter {$parameter->getName()}"
                    );
                }
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
