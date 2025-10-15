<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use Exception;
use RagSystem\Infrastructure\Service\LlamaCppAdapter;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LLMService
{
    public function __construct(
        private LlamaCppAdapter $llamaCppAdapter,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Генерирует ответ на основе промпта и контекста
     */
    public function generateResponse(string $prompt, array $context = []): string
    {
        $fullPrompt = $this->buildPrompt($prompt, $context);

        try {
            $response = $this->llamaCppAdapter->generateCompletion($fullPrompt);

            $this->logger->debug('Generated LLM response', [
                'prompt_length' => strlen($fullPrompt),
                'response_length' => strlen($response)
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Failed to generate LLM response', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($fullPrompt)
            ]);

            throw new RuntimeException('Failed to generate response: ' . $e->getMessage());
        }
    }

    /**
     * Генерирует ответ на основе вопроса и релевантных фрагментов документов
     */
    public function generateRAGResponse(string $question, array $relevantChunks): string
    {
        $contextText = $this->formatContextFromChunks($relevantChunks);

        $prompt = $this->buildRAGPrompt($question, $contextText);

        $this->logger->debug('Generated RAG prompt', [
            'prompt_preview' => substr($prompt, 0, 300) . '...',
            'prompt_length' => strlen($prompt)
        ]);

        try {
            $processedPrompt = $this->llamaCppAdapter->processRAGPrompt($prompt);
            $response = $this->llamaCppAdapter->generateCompletion($processedPrompt);

            $this->logger->debug('Generated RAG response', [
                'prompt_length' => strlen($processedPrompt),
                'response_length' => strlen($response)
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('Failed to generate RAG response', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt)
            ]);

            throw new RuntimeException('Failed to generate response: ' . $e->getMessage());
        }
    }

    /**
     * Создает промпт для языковой модели с учетом контекста
     */
    private function buildPrompt(string $userPrompt, array $context = []): string
    {
        $systemPrompt = "You are a helpful AI assistant. " .
            "Provide accurate and helpful responses based on the given context.";

        $prompt = "{$systemPrompt}\n\n";

        if (!empty($context)) {
            $prompt .= "Context:\n" . implode("\n", $context) . "\n\n";
        }

        $prompt .= "User: {$userPrompt}\n\nAssistant:";

        return $prompt;
    }

    /**
     * Создает промпт с контекстом и вопросом пользователя
     */
    private function buildRAGPrompt(string $question, string $context): string
    {
        $language = $this->detectLanguage($question);

        if ($language === 'en') {
            $systemPrompt = "You are an AI assistant for working with documentation. " .
                "Answer only in English based on the provided context.";
            $prompt = "{$systemPrompt}\n\n";
            $prompt .= "Context:\n{$context}\n\n";
            $prompt .= "Question: {$question}\n\n";
            $prompt .= "Answer in English:";
        } else {
            $systemPrompt = "Ты - русскоязычный AI-ассистент для работы с документацией. " .
                "Отвечай только на русском языке на основе предоставленного контекста.";
            $prompt = "{$systemPrompt}\n\n";
            $prompt .= "Контекст:\n{$context}\n\n";
            $prompt .= "Вопрос: {$question}\n\n";
            $prompt .= "Ответ на русском языке:";
        }

        return $prompt;
    }

    /**
     * Определяет язык текста (русский или английский)
     */
    private function detectLanguage(string $text): string
    {
        $text = strtolower($text);

        $englishWords = [
            'the', 'is', 'are', 'was', 'were', 'and', 'or', 'but', 'if', 'then',
            'what', 'who', 'how', 'when', 'where', 'why', 'which', 'that', 'this',
            'these', 'those', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'
        ];
        $englishCount = 0;

        foreach ($englishWords as $word) {
            if (str_contains($text, $word)) {
                $englishCount++;
            }
        }

        $russianWords = [
            'что', 'как', 'где', 'когда', 'зачем', 'почему', 'кто', 'какой', 'какая',
            'какие', 'это', 'есть', 'был', 'была', 'были', 'будет', 'будут', 'может',
            'должен', 'должна', 'должны', 'для', 'про', 'расскажи', 'объясни', 'покажи'
        ];
        $russianCount = 0;

        foreach ($russianWords as $word) {
            if (str_contains($text, $word)) {
                $russianCount++;
            }
        }

        $cyrillicCount = preg_match_all('/[а-яё]/u', $text);
        $latinCount = preg_match_all('/[a-z]/u', $text);

        if ($cyrillicCount > 0 || $russianCount > $englishCount) {
            return 'ru';
        } elseif ($latinCount > 0 || $englishCount > $russianCount) {
            return 'en';
        } else {
            return 'ru';
        }
    }

    /**
     * Форматирует массив чанков документов в единый текстовый контекст
     */
    private function formatContextFromChunks(array $chunks): string
    {
        $contextParts = [];

        foreach ($chunks as $chunk) {
            if (is_array($chunk) && isset($chunk['chunk_text'])) {
                $contextParts[] = $chunk['chunk_text'];
            } elseif (is_string($chunk)) {
                $contextParts[] = $chunk;
            }
        }

        return implode("\n\n", $contextParts);
    }

    /**
     * Проверяет готовность работы LLM сервиса
     */
    public function checkHealth(): bool
    {
        return $this->llamaCppAdapter->ensureModelLoaded();
    }
}
