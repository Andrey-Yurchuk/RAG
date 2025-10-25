<?php

declare(strict_types=1);

namespace RagSystem\Tests\Helpers;

use Doctrine\DBAL\Connection;
use RuntimeException;

class MockDatabaseConnection
{
    private array $data = [];
    private bool $shouldFail = false;
    private string $failureMessage = 'Mock database failure';

    /**
     * Установка, должен ли адаптер симулировать сбои
     */
    public function setShouldFail(bool $shouldFail, string $message = 'Mock database failure'): void
    {
        $this->shouldFail = $shouldFail;
        $this->failureMessage = $message;
    }

    /**
     * Установка мок-данных для запросов
     */
    public function setMockData(string $table, array $data): void
    {
        $this->data[$table] = $data;
    }

    /**
     * Мок метода fetchAllAssociative
     */
    public function fetchAllAssociative(string $sql, array $params = [], $types = []): array
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        return $this->getMockDataForQuery($sql, $params);
    }

    /**
     * Мок метода fetchAssociative
     */
    public function fetchAssociative(string $sql, array $params = [], $types = []): array|false
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        $data = $this->getMockDataForQuery($sql, $params);
        return $data[0] ?? false;
    }

    /**
     * Получение мок-данных на основе SQL запроса
     */
    private function getMockDataForQuery(string $sql, array $params): array
    {
        $sql = strtolower($sql);

        if (strpos($sql, 'select') === 0) {
            if (strpos($sql, 'documents') !== false) {
                return $this->data['documents'] ?? [];
            }
            if (strpos($sql, 'document_chunks') !== false) {
                return $this->data['document_chunks'] ?? [];
            }
            if (strpos($sql, 'queries') !== false) {
                return $this->data['queries'] ?? [];
            }
            if (strpos($sql, 'users') !== false) {
                return $this->data['users'] ?? [];
            }
        }
        
        return [];
    }
}
