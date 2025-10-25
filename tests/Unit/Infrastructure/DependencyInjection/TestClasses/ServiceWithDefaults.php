<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Infrastructure\DependencyInjection\TestClasses;

/**
 * Тестовый сервис с параметрами по умолчанию для Container
 */
class ServiceWithDefaults
{
    public function __construct(
        private string $name = 'default',
        private int $count = 0,
        private bool $enabled = true
    ) {
    }
}
