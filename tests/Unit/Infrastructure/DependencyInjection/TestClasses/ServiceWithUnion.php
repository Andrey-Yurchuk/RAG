<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses;

/**
 * Тестовый сервис с union типами для Container
 */
class ServiceWithUnion
{
    public function __construct(private string|int $union)
    {
    }
}
