<?php

declare(strict_types=1);

namespace RagSystem\Infrastructure\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

class LlamaCppAdapter
{
    private string $llamaCppUrl;
    private string $modelName;
    private array $embeddingCache = [];
    private bool $modelLoaded = false;

    public function __construct(
        private ?Client $httpClient = null,
        private ?Logger $logger = null,
        private array $config = []
    ) {
        $this->httpClient ??= new Client(['timeout' => 60]);
        $this->logger ??= new Logger('llm-adapter');
        $this->llamaCppUrl = $this->config['llm']['service_url'];
        $this->modelName = $this->config['llm']['model_name'];
        $logPath = $this->config['storage']['log_path'];
        $this->logger->pushHandler(new StreamHandler($logPath, Level::Info));
    }

    /**
     * Проверяет доступность и готовность сервера llama.cpp к работе
     */
    public function ensureModelLoaded(): bool
    {
        try {
            $response = $this->httpClient->get($this->llamaCppUrl . '/health');

            if ($response->getStatusCode() === 200) {
                $this->modelLoaded = true;
                return true;
            }

            $this->logger->warning('llama.cpp server not ready', ['status' => $response->getStatusCode()]);
            return false;
        } catch (RequestException $e) {
            $this->logger->error('Error checking llama.cpp server', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Генерирует векторное представление текста (эмбеддинг) с использованием сервера llama.cpp
     */
    public function generateEmbedding(string $text): array
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Простое кэширование на основе хеша текста
        $textHash = md5($text);
        if (isset($this->embeddingCache[$textHash])) {
            return $this->embeddingCache[$textHash];
        }

        try {
            $response = $this->httpClient->post($this->llamaCppUrl . '/embedding', [
                'json' => ['content' => $text],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $embedding = $data['embedding'] ?? [];

                // Обеспечиваем точную размерность эмбеддинга 1536 элементов
                if (count($embedding) > 1536) {
                    $embedding = array_slice($embedding, 0, 1536);
                } elseif (count($embedding) < 1536) {
                    $embedding = array_merge($embedding, array_fill(0, 1536 - count($embedding), 0.0));
                }

                $this->embeddingCache[$textHash] = $embedding;
                return $embedding;
            }

            $this->logger->warning('Embedding generation failed', ['status' => $response->getStatusCode()]);
            return $this->generateSimpleEmbedding($text);
        } catch (RequestException $e) {
            $this->logger->error('Error generating embedding', ['error' => $e->getMessage()]);
            return $this->generateSimpleEmbedding($text);
        }
    }

    /**
     * Генерирует простое векторное представление текста (эмбеддинги) без использования внешнего сервера.
     * Этот метод используется как резервный вариант, когда сервер llama.cpp недоступен.
     * Создает эмбеддинг на основе байтового представления текста с применением математических преобразований.
     * Алгоритм включает нормализацию и заполнение до указанной размерности
     */
    public function generateSimpleEmbedding(string $text, int $dimension = 1536): array
    {
        $text = strtolower(trim($text));
        $textBytes = unpack('C*', $text);
        $embedding = [];

        for ($i = 0; $i < $dimension; $i++) {
            if ($i < count($textBytes)) {
                $value = ($textBytes[$i + 1] / 127.5) - 1.0;
            } else {
                $charSum = array_sum($textBytes);
                $value = (($charSum + $i) % 255 / 127.5) - 1.0;
            }
            $embedding[] = $value;
        }

        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $embedding)));
        if ($norm > 0) {
            $embedding = array_map(fn($x) => $x / $norm, $embedding);
        }

        return $embedding;
    }

    /**
     * Оптимизирует промпт для системы RAG на основе типа вопроса.
     * Метод анализирует вопрос пользователя и создает специализированный промпт,
     * который помогает языковой модели дать более точный и структурированный ответ
     */
    public function optimizeRagPrompt(string $context, string $question): string
    {
        $questionLower = strtolower($question);

        if (preg_match('/как|how|пошагов|step|инструкц|instruction/u', $questionLower)) {
            $instruction = "Используя предоставленный контекст, дай подробное объяснение процесса или инструкции. Если в контексте есть пошаговые указания, включи их полностью.";
        } elseif (preg_match('/что такое|what is|определен|definition|означа|mean/u', $questionLower)) {
            $instruction = "На основе контекста дай четкое определение или объяснение запрашиваемого понятия. Укажи его назначение и основные характеристики.";
        } elseif (preg_match('/чем|difference|различи|сравн|compare|лучше|worse|advantage/u', $questionLower)) {
            $instruction = "Используя контекст, проведи сравнение или анализ различий. Укажи преимущества, недостатки и особенности.";
        } elseif (preg_match('/где|where|найти|find|расположен|located/u', $questionLower)) {
            $instruction = "На основе контекста укажи, где можно найти или как получить доступ к запрашиваемому элементу.";
        } elseif (preg_match('/когда|when|время|time|срок|deadline/u', $questionLower)) {
            $instruction = "Используя контекст, объясни временные аспекты, сроки или условия, связанные с запросом.";
        } else {
            $instruction = "Внимательно проанализируй предоставленный контекст и дай полный, информативный ответ на вопрос.";
        }

        return "Ты - AI-ассистент для работы с документацией. Важно: отвечай на русском языке.

        {$instruction}

        Контекст из документации:
        {$context}

        Вопрос: {$question}

        ОБЯЗАТЕЛЬНЫЕ требования к ответу:
        - Отвечай на русском языке
        - Используй только информацию из предоставленного контекста
        - Структурируй ответ четко и логично
        - Если в контексте есть пошаговые инструкции, включи их полностью
        - Если контекст не содержит нужной информации, скажи: \"В предоставленном контексте нет информации для ответа на этот вопрос\"

        Твой ответ на русском языке:";
    }

    /**
     * Генерирует ответ от языковой модели на основе промпта.
     * Метод отправляет промпт на сервер llama.cpp и получает ответ от языковой модели.
     * Промпт форматируется в соответствии с требованиями модели TinyLlama.
     *
     * Параметры генерации:
     * - n_predict: 256 токенов (~200 слов) - оптимальная длина для RAG ответов
     * - temperature: 0.1 - низкая креативность для точных ответов
     * - top_k: 10 - ограничение выбора для стабильности
     * - top_p: 0.3 - ядерная выборка для качества
     * - repeat_penalty: 1.1 - предотвращение повторов
     */
    public function generateCompletion(string $prompt): string
    {
        if (!$this->ensureModelLoaded()) {
            return 'Извините, модель временно недоступна. Попробуйте позже.';
        }

        $prompt = mb_convert_encoding($prompt, 'UTF-8', 'UTF-8');
        $prompt = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $prompt);
        
        $formattedPrompt = "<|user|>\n{$prompt}\n<|assistant|>\n";

        try {
            $response = $this->httpClient->post($this->llamaCppUrl . '/completion', [
                'json' => [
                    'prompt' => $formattedPrompt,
                    'n_predict' => 256,
                    'temperature' => 0.1,
                    'top_k' => 10,
                    'top_p' => 0.3,
                    'repeat_penalty' => 1.1,
                    'stop' => ["<|user|>", "<|assistant|>", "<|system|>"]
                ],
                'timeout' => 90
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $content = $data['content'] ?? 'Не удалось получить ответ от модели.';

                $content = trim($content);

                $content = preg_replace('/<\|[^|]*\|>/', '', $content);

                return trim($content);
            }

            $this->logger->warning('Completion generation failed', ['status' => $response->getStatusCode()]);
            return 'Произошла ошибка при генерации ответа.';
        } catch (RequestException $e) {
            $this->logger->error('Error generating completion', ['error' => $e->getMessage()]);
            return 'Произошла ошибка при обращении к модели.';
        }
    }

    /**
     * Обрабатывает RAG промпт и оптимизирует его для лучшего ответа
     *
     * Метод анализирует входящий промпт и определяет, содержит ли он структурированный
     * контекст и вопрос. Если да, то применяет оптимизацию для улучшения качества ответа.
     *
     * Поддерживает два формата:
     * - Русский: "Контекст: ... Вопрос: ..."
     * - Английский: "Context: ... Question: ..."
     */
    public function processRAGPrompt(string $prompt): string
    {
        if (str_contains($prompt, 'Контекст:')) {
            $contextStart = strpos($prompt, 'Контекст:');
            $contextEnd = strpos($prompt, "\n\nВопрос:");
            if ($contextEnd === false) $contextEnd = strlen($prompt);

            $context = trim(substr($prompt, $contextStart + 9, $contextEnd - $contextStart - 9));

            $questionStart = strpos($prompt, 'Вопрос:');
            if ($questionStart !== false) {
                $question = trim(substr($prompt, $questionStart + 7));

                if (str_ends_with($question, "\n\nОтвет на русском языке:")) {
                    $question = trim(substr($question, 0, -26));
                }

                return $this->optimizeRagPrompt($context, $question);
            }
        } elseif (str_contains($prompt, 'Context:')) {
            $contextStart = strpos($prompt, 'Context:');
            $contextEnd = strpos($prompt, "\n\nQuestion:");
            if ($contextEnd === false) $contextEnd = strlen($prompt);

            $context = trim(substr($prompt, $contextStart + 8, $contextEnd - $contextStart - 8));

            $questionStart = strpos($prompt, 'Question:');
            if ($questionStart !== false) {
                $question = trim(substr($prompt, $questionStart + 9));

                if (str_ends_with($question, "\n\nAnswer in English:")) {
                    $question = trim(substr($question, 0, -20));
                }

                $optimizedPrompt = "You are an AI assistant for working with documentation. Important: Answer in English!

                Context from documentation:
                {$context}

                Question: {$question}

                MANDATORY requirements for the answer:
                - Answer in English
                - Use only information from the provided context
                - Structure the answer clearly and logically
                - If the context contains step-by-step instructions, include them fully
                - If the context doesn't contain the needed information, say: \"The provided context does not contain information to answer this question\"

                Your answer in English:";

                return $optimizedPrompt;
            }
        }

        return $prompt;
    }
}