<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Database\Platforms;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

class PostgreSQLVectorPlatform extends PostgreSQLPlatform
{
    public function __construct()
    {
        $this->registerDoctrineTypeMapping('vector', 'vector');
        $this->registerDoctrineTypeMapping('uuid', 'guid');
    }

    /**
     * Возвращает маппинг типа базы данных на тип Doctrine DBAL
     *
     * @param string $dbType
     */
    public function getDoctrineTypeMapping($dbType): string
    {
        return match ($dbType) {
            'vector' => 'vector',
            'uuid' => 'guid',
            default => parent::getDoctrineTypeMapping($dbType),
        };
    }
}
