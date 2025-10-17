<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use Redis;
use Psr\Log\LoggerInterface;

class TaskStatusService
{
    private Redis $redis;
    private string $prefix;

    public function __construct(
        string $redisHost,
        int $redisPort,
        string $redisPassword,
        string $prefix,
        LoggerInterface $logger
    ) {
        $this->redis = new Redis();
        $this->redis->connect($redisHost, $redisPort);
        $this->redis->auth($redisPassword);
        $this->prefix = $prefix;
    }

    /**
     * Обновляет статус обработки документа
     */
    public function updateDocumentProcessingStatus(
        string $documentId,
        string $status,
        int $processed = 0,
        int $total = 0,
        ?string $error = null
    ): void {
        $key = $this->prefix . "document_processing:{$documentId}";
        
        $data = [
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'updated_at' => time()
        ];
        
        if ($error) {
            $data['error'] = $error;
        }
        
        $this->redis->hMSet($key, $data);
        $this->redis->expire($key, 3600); // TTL 1 час
    }

    /**
     * Получает статус обработки документа
     */
    public function getDocumentProcessingStatus(string $documentId): array
    {
        $key = $this->prefix . "document_processing:{$documentId}";
        $data = $this->redis->hGetAll($key);
        
        if (empty($data)) {
            return [
                'status' => 'not_found',
                'processed' => 0,
                'total' => 0,
                'percentage' => 0,
                'updated_at' => 0
            ];
        }
        
        return $data;
    }

    /**
     * Обновляет статус генерации эмбеддинга
     */
    public function updateEmbeddingGenerationStatus(
        string $chunkId,
        string $status,
        ?string $error = null
    ): void {
        $key = $this->prefix . "embedding_generation:{$chunkId}";
        
        $data = [
            'status' => $status,
            'updated_at' => time()
        ];
        
        if ($error) {
            $data['error'] = $error;
        }
        
        $this->redis->hMSet($key, $data);
        $this->redis->expire($key, 1800); // TTL 30 минут
    }

    /**
     * Получает статус генерации эмбеддинга
     */
    public function getEmbeddingGenerationStatus(string $chunkId): array
    {
        $key = $this->prefix . "embedding_generation:{$chunkId}";
        $data = $this->redis->hGetAll($key);
        
        if (empty($data)) {
            return [
                'status' => 'not_found',
                'updated_at' => 0
            ];
        }
        
        return $data;
    }

    /**
     * Удаляет статус задачи
     */
    public function removeTaskStatus(string $taskType, string $taskId): void
    {
        $key = $this->prefix . "{$taskType}:{$taskId}";
        $this->redis->del($key);
    }

    /**
     * Получает все активные задачи по типу
     */
    public function getActiveTasks(string $taskType): array
    {
        $pattern = $this->prefix . "{$taskType}:*";
        $keys = $this->redis->keys($pattern);
        
        $tasks = [];
        foreach ($keys as $key) {
            $data = $this->redis->hGetAll($key);
            if (!empty($data)) {
                $tasks[] = $data;
            }
        }
        
        return $tasks;
    }

    /**
     * Очищает устаревшие статусы задач
     */
    public function cleanupExpiredTasks(): int
    {
        $patterns = [
            $this->prefix . "document_processing:*",
            $this->prefix . "embedding_generation:*",
            $this->prefix . "user_notification:*"
        ];
        
        $cleaned = 0;
        foreach ($patterns as $pattern) {
            $keys = $this->redis->keys($pattern);
            foreach ($keys as $key) {
                $ttl = $this->redis->ttl($key);
                if ($ttl === -1) { // Ключ без TTL
                    $this->redis->expire($key, 3600); // Устанавливаем TTL
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}
