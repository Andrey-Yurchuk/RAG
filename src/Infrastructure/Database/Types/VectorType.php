<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Database\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PDO;

class VectorType extends Type
{
    public const string VECTOR = 'vector';

    /**
     * Возвращает SQL-декларацию для векторного типа
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'vector';
    }

    /**
     * Возвращает имя типа данных
     */
    public function getName(): string
    {
        return self::VECTOR;
    }

    /**
     * Возвращает тип привязки для PDO
     */
    public function getBindingType(): int
    {
        return PDO::PARAM_STR;
    }

    /**
     * Возвращает список типов базы данных, которые маппятся на этот Doctrine тип
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['vector'];
    }
}
