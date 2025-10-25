<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses;

/**
 * Тестовый сервис с неразрешимыми параметрами для Container
 */
class ServiceWithUnresolvable
{
    public function __construct(private string $unresolvable)
    {
    }
}
