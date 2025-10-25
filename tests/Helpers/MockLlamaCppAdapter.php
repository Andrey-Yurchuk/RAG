<?php

declare(strict_types=1);

namespace RagSystem\Tests\Helpers;

use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use RuntimeException;

class MockLlamaCppAdapter extends LlamaCppAdapter
{
    private bool $isHealthy = true;
    private array $embeddingCache = [];
    private array $completionCache = [];
    private bool $shouldFail = false;
    private string $failureMessage = 'Mock failure';

    public function __construct()
    {
        //parent::__construct();
    }

    /**
     * Установка, должен ли адаптер симулировать сбои
     */
    public function setShouldFail(bool $shouldFail, string $message = 'Мок-сбой'): void
    {
        $this->shouldFail = $shouldFail;
        $this->failureMessage = $message;
    }

    /**
     * Установка статуса здоровья
     */
    public function setHealthy(bool $isHealthy): void
    {
        $this->isHealthy = $isHealthy;
    }

    /**
     * Мок ensureModelLoaded
     */
    public function ensureModelLoaded(): bool
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }
        
        return $this->isHealthy;
    }

    /**
     * Мок generateEmbedding
     */
    public function generateEmbedding(string $text): array
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (isset($this->embeddingCache[$text])) {
            return $this->embeddingCache[$text];
        }

        $embedding = $this->generateMockEmbedding($text);
        $this->embeddingCache[$text] = $embedding;
        
        return $embedding;
    }

    /**
     * Мок generateCompletion
     */
    public function generateCompletion(string $prompt): string
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (isset($this->completionCache[$prompt])) {
            return $this->completionCache[$prompt];
        }

        $completion = $this->generateMockCompletion($prompt);
        $this->completionCache[$prompt] = $completion;
        
        return $completion;
    }

    /**
     * Мок processRAGPrompt
     */
    public function processRAGPrompt(string $prompt): string
    {
        if ($this->shouldFail) {
            throw new RuntimeException($this->failureMessage);
        }

        if (strlen($prompt) > 4000) {
            return substr($prompt, 0, 4000) . '...';
        }

        return $prompt;
    }

    /**
     * Генерация мок-эмбеддинга на основе текста
     */
    private function generateMockEmbedding(string $text): array
    {
        $hash = md5($text);
        $embedding = [];
        
        for ($i = 0; $i < 384; $i++) {
            $seed = hexdec(substr($hash, $i % 32, 1)) / 15.0;
            $embedding[] = ($seed - 0.5) * 2;
        }
        
        return $embedding;
    }

    /**
     * Генерация мок-завершения на основе промпта
     */
    private function generateMockCompletion(string $prompt): string
    {
        if (strpos($prompt, 'вопрос') !== false || strpos($prompt, '?') !== false || 
            strpos($prompt, 'question') !== false) {
            return 'Это мок-ответ на ваш вопрос.';
        }
        
        if (strpos($prompt, 'переведи') !== false || strpos($prompt, 'translate') !== false) {
            return 'Результат мок-перевода.';
        }
        
        if (strpos($prompt, 'суммар') !== false || strpos($prompt, 'summarize') !== false) {
            return 'Мок-резюме предоставленного текста.';
        }
        
        if (strpos($prompt, 'документ') !== false || strpos($prompt, 'document') !== false) {
            return 'Этот документ содержит информацию о тестировании системы RAG.';
        }
        
        return 'Мок-ответ системы.';
    }

    /**
     * Очистка кэшей
     */
    public function clearCaches(): void
    {
        $this->embeddingCache = [];
        $this->completionCache = [];
    }

    /**
     * Получение статистики кэша
     */
    public function getCacheStats(): array
    {
        return [
            'embedding_cache_size' => count($this->embeddingCache),
            'completion_cache_size' => count($this->completionCache),
        ];
    }
}
