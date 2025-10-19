<?php

declare(strict_types=1);

return [
    'llm' => [
        'service_url' => $_ENV['LLAMA_CPP_URL'] ?? 'http://llm-service:8080',
        'model_name' => $_ENV['MODEL_NAME'] ?? $_ENV['LLM_MODEL_PATH'] ?? 'qwen2.5-7b-instruct-q4_k_m.gguf',
        'max_tokens' => (int)($_ENV['LLM_MAX_TOKENS'] ?? 1024),
        'temperature' => (float)($_ENV['LLM_TEMPERATURE'] ?? 0.1),
        'context_size' => (int)($_ENV['LLM_CONTEXT_SIZE'] ?? 4096),
        'timeout' => (int)($_ENV['LLM_TIMEOUT'] ?? 300),
    ],

    'embedding' => [
        'dimension' => (int)($_ENV['EMBEDDING_DIMENSION'] ?? 1536),
        'chunk_size' => (int)($_ENV['CHUNK_SIZE'] ?? 300),
        'chunk_overlap' => (int)($_ENV['CHUNK_OVERLAP'] ?? 50),
    ],

    'vector_search' => [
        'limit' => (int)($_ENV['VECTOR_SEARCH_LIMIT'] ?? 5),
        'similarity_threshold' => (float)($_ENV['VECTOR_SEARCH_THRESHOLD'] ?? 0.3),
    ],

    'hybrid_search' => [
        'vector_top_k' => (int)($_ENV['HYBRID_SEARCH_VECTOR_TOP_K'] ?? 10),
        'keyword_top_k' => (int)($_ENV['HYBRID_SEARCH_KEYWORD_TOP_K'] ?? 10),
        'rrf_k' => (int)($_ENV['HYBRID_SEARCH_RRF_K'] ?? 60),
    ],

    'storage' => [
        'upload_path' => $_ENV['STORAGE_UPLOAD_PATH'] ?? '/app/storage/uploads/',
        'log_path' => $_ENV['LOG_FILE'] ?? '/app/storage/logs/app.log',
    ],
];
