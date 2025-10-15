<?php

declare(strict_types=1);

namespace RagSystem\Application\DTO;

interface DTOInterface
{
    /**
     * Создает DTO из массива данных
     */
    public static function fromArray(array $data): self;

    /**
     * Преобразует DTO в массив
     */
    public function toArray(): array;
}
