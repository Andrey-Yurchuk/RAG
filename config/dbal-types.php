<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use RagSystem\Infrastructure\Database\Types\VectorType;

// Register custom vector type for pgvector
if (!Type::hasType('vector')) {
    Type::addType('vector', VectorType::class);
}
