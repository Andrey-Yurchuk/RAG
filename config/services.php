<?php

declare(strict_types=1);

return [
    'llm' => [
        'service_url' => $_ENV['LLAMA_CPP_URL'] ?? 'http://llm-service:8080',
        'model_name' => $_ENV['MODEL_NAME'] ?? 'tinyllama-1.1b-chat-v1.0.Q4_K_M.gguf',
        'max_tokens' => (int)($_ENV['LLM_MAX_TOKENS'] ?? 256),
        'temperature' => (float)($_ENV['LLM_TEMPERATURE'] ?? 0.1),
        'context_size' => (int) ($_ENV['LLM_CONTEXT_SIZE'] ?? 2048),
        'timeout' => (int)($_ENV['LLM_TIMEOUT'] ?? 90),
    ],

    'embedding' => [
        'model' => $_ENV['EMBEDDING_MODEL'] ?? 'sentence-transformers/all-MiniLM-L6-v2',
        'dimension' => (int)($_ENV['EMBEDDING_DIMENSION'] ?? 1536),
        'chunk_size' => (int)($_ENV['CHUNK_SIZE'] ?? 300),
        'chunk_overlap' => (int)($_ENV['CHUNK_OVERLAP'] ?? 50),
    ],

    'vector_search' => [
        'limit' => (int)($_ENV['VECTOR_SEARCH_LIMIT'] ?? 5),
        'similarity_threshold' => (float)($_ENV['VECTOR_SEARCH_THRESHOLD'] ?? 0.001),
    ],

    'storage' => [
        'upload_path' => '/app/storage/uploads/',
        'log_path' => $_ENV['LOG_FILE'] ?? '/app/storage/logs/app.log',
    ],
];