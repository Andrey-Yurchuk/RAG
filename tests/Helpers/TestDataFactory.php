<?php

declare(strict_types=1);

namespace RagSystem\Tests\Helpers;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use DateTimeImmutable;

class TestDataFactory
{
    /**
     * Создание тестового документа
     */
    public static function createDocument(array $overrides = []): array
    {
        $defaults = [
            'id' => Uuid::uuid4()->toString(),
            'title' => 'Тестовый документ',
            'content' => 'Это содержимое тестового документа. Оно содержит образец текста для целей тестирования.',
            'file_path' => '/test/path/document.txt',
            'file_type' => 'txt',
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание тестового фрагмента документа
     */
    public static function createDocumentChunk(array $overrides = []): array
    {
        $defaults = [
            'id' => Uuid::uuid4()->toString(),
            'document_id' => Uuid::uuid4()->toString(),
            'chunk_text' => 'Это тестовый фрагмент текста.',
            'chunk_index' => 0,
            'embedding' => [0.1, 0.2, 0.3, 0.4, 0.5],
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание тестового запроса
     */
    public static function createQuery(array $overrides = []): array
    {
        $defaults = [
            'id' => Uuid::uuid4()->toString(),
            'query_text' => 'О чем этот документ?',
            'query_embedding' => [0.1, 0.2, 0.3, 0.4, 0.5],
            'response' => 'Этот документ о тестировании.',
            'response_time' => 1.5,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание тестового пользователя
     */
    public static function createUser(array $overrides = []): array
    {
        $defaults = [
            'id' => Uuid::uuid4()->toString(),
            'username' => 'тестовый_пользователь',
            'email' => 'test@example.com',
            'password_hash' => password_hash('пароль123', PASSWORD_DEFAULT),
            'role' => 'user',
            'is_active' => true,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание тестовой пользовательской сессии
     */
    public static function createUserSession(array $overrides = []): array
    {
        $defaults = [
            'id' => Uuid::uuid4()->toString(),
            'user_id' => Uuid::uuid4()->toString(),
            'token' => 'test_token_' . bin2hex(random_bytes(16)),
            'expires_at' => (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание нескольких тестовых документов
     */
    public static function createDocuments(int $count, array $baseOverrides = []): array
    {
        $documents = [];
        for ($i = 0; $i < $count; $i++) {
            $overrides = array_merge($baseOverrides, [
                'title' => "Тестовый документ {$i}",
                'content' => "Это тестовый документ номер {$i}. Он содержит образец контента для тестирования.",
            ]);
            $documents[] = self::createDocument($overrides);
        }
        return $documents;
    }

    /**
     * Создание нескольких тестовых фрагментов для документа
     */
    public static function createDocumentChunks(string $documentId, int $count, array $baseOverrides = []): array
    {
        $chunks = [];
        for ($i = 0; $i < $count; $i++) {
            $overrides = array_merge($baseOverrides, [
                'document_id' => $documentId,
                'chunk_text' => "Это фрагмент номер {$i} документа.",
                'chunk_index' => $i,
            ]);
            $chunks[] = self::createDocumentChunk($overrides);
        }
        return $chunks;
    }

    /**
     * Создание нескольких тестовых запросов
     */
    public static function createQueries(int $count, array $baseOverrides = []): array
    {
        $queries = [];
        for ($i = 0; $i < $count; $i++) {
            $overrides = array_merge($baseOverrides, [
                'query_text' => "Тестовый запрос номер {$i}?",
                'response' => "Это ответ номер {$i}.",
            ]);
            $queries[] = self::createQuery($overrides);
        }
        return $queries;
    }

    /**
     * Создание тестового содержимого файла
     */
    public static function createFileContent(string $type = 'txt'): string
    {
        return match ($type) {
            'txt' => "Это тестовый текстовый файл.\nОн содержит несколько строк контента.\nОтлично подходит для тестирования обработки текста.",
            'md' => "# Тестовый Markdown\n\nЭто **тестовый** markdown файл.\n\n- Элемент 1\n- Элемент 2\n\nОтлично для тестирования!",
            'html' => "<html><body><h1>Тестовый HTML</h1><p>Это тестовый HTML файл.</p><p>Он содержит <strong>HTML теги</strong>.</p></body></html>",
            'pdf' => "Симуляция PDF контента - в реальных тестах здесь были бы бинарные данные PDF",
            'docx' => "Симуляция DOCX контента - в реальных тестах здесь были бы бинарные данные DOCX",
            'doc' => "Симуляция DOC контента - в реальных тестах здесь были бы бинарные данные DOC",
            default => "Тестовый контент для файла типа {$type}.",
        };
    }

    /**
     * Создание тестового вектора эмбеддинга
     */
    public static function createEmbedding(int $dimensions = 384): array
    {
        $embedding = [];
        for ($i = 0; $i < $dimensions; $i++) {
            $embedding[] = (mt_rand(-1000, 1000) / 1000.0);
        }
        return $embedding;
    }

    /**
     * Создание тестовых результатов поиска
     */
    public static function createSearchResults(int $count = 5): array
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = [
                'chunk_text' => "Search result chunk {$i}",
                'similarity_score' => 0.9 - ($i * 0.1),
                'document_id' => Uuid::uuid4()->toString(),
                'chunk_index' => $i,
            ];
        }
        return $results;
    }

    /**
     * Создание тестовых данных API запроса
     */
    public static function createApiRequest(string $endpoint, array $data = []): array
    {
        return [
            'endpoint' => $endpoint,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer test_token',
            ],
            'body' => $data,
        ];
    }

    /**
     * Создание тестового API ответа
     */
    public static function createApiResponse(bool $success = true, array $data = [], string $message = ''): array
    {
        $response = [
            'success' => $success,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Создание тестового ответа с ошибкой
     */
    public static function createErrorResponse(string $message, array $errors = [], int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ];
    }

    /**
     * Создание тестового массива конфигурации
     */
    public static function createConfig(array $overrides = []): array
    {
        $defaults = [
            'vector_search' => [
                'limit' => 10,
                'similarity_threshold' => 0.8,
            ],
            'hybrid_search' => [
                'vector_top_k' => 20,
                'keyword_top_k' => 20,
            ],
            'llm' => [
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ],
            'chunking' => [
                'chunk_size' => 1000,
                'overlap' => 200,
            ],
        ];

        return array_merge_recursive($defaults, $overrides);
    }

    /**
     * Создание тестовых данных задачи
     */
    public static function createTask(string $type, array $overrides = []): array
    {
        $defaults = [
            'type' => $type,
            'status' => 'pending',
            'created_at' => time(),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание тестовой задачи обработки документа
     */
    public static function createDocumentProcessingTask(array $overrides = []): array
    {
        $defaults = [
            'type' => 'document_processing',
            'document_id' => Uuid::uuid4()->toString(),
            'file_path' => '/test/path/document.txt',
            'file_type' => 'txt',
            'content' => self::createFileContent('txt'),
            'status' => 'pending',
            'created_at' => time(),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Создание тестовой задачи генерации эмбеддинга
     */
    public static function createEmbeddingGenerationTask(array $overrides = []): array
    {
        $defaults = [
            'type' => 'embedding_generation',
            'chunk_id' => Uuid::uuid4()->toString(),
            'chunk_text' => 'Test chunk text for embedding generation.',
            'document_id' => Uuid::uuid4()->toString(),
            'chunk_index' => 0,
            'status' => 'pending',
            'created_at' => time(),
        ];

        return array_merge($defaults, $overrides);
    }
}
