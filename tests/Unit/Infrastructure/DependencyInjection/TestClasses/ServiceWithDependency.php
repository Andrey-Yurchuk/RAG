<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses;

/**
 * Тестовый сервис с зависимостью для Container
 */
class ServiceWithDependency
{
    public function __construct(private DependencyClass $dependency)
    {
    }
}
