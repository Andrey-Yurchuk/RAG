<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\DependencyInjection;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    public function __construct(
        private array $bindings = [],
        private array $instances = []
    ) {}

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

    public function get(string $id)
    {
        // TODO: Implement get() method.
    }

    public function has(string $id): bool
    {
        // TODO: Implement has() method.
    }
}
