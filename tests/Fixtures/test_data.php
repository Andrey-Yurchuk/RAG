<?php

declare(strict_types=1);

/**
 * Test data fixtures
 * 
 * This file contains sample data for testing purposes
 */

// Sample document content for different file types
const TEST_DOCUMENT_CONTENT = [
    'txt' => "This is a comprehensive test document for the RAG system.\n\nIt contains multiple paragraphs with various information that can be used for testing document processing, chunking, and retrieval.\n\nThe document covers topics like artificial intelligence, machine learning, natural language processing, and information retrieval systems.\n\nThis content is designed to be realistic enough for testing purposes while remaining simple and predictable.",
    
    'md' => "# RAG System Test Document\n\n## Introduction\n\nThis is a **test document** for the RAG (Retrieval-Augmented Generation) system.\n\n### Key Features\n\n- Document processing\n- Text chunking\n- Vector embeddings\n- Semantic search\n- LLM integration\n\n## Content\n\nThis document contains sample content that can be used for testing various components of the RAG system.\n\n> This is a blockquote for testing markdown processing.\n\n## Conclusion\n\nThis test document provides sufficient content for comprehensive testing.",
    
    'html' => "<!DOCTYPE html>\n<html>\n<head>\n    <title>RAG System Test Document</title>\n</head>\n<body>\n    <h1>RAG System Test Document</h1>\n    <p>This is a test HTML document for the RAG system.</p>\n    <h2>Features</h2>\n    <ul>\n        <li>Document processing</li>\n        <li>Text extraction</li>\n        <li>Content analysis</li>\n    </ul>\n    <p>This document contains <strong>HTML tags</strong> and <em>formatting</em> for testing purposes.</p>\n</body>\n</html>",
];

// Sample queries for testing
const TEST_QUERIES = [
    'What is this document about?',
    'What are the main features mentioned?',
    'How does the RAG system work?',
    'What is artificial intelligence?',
    'Explain machine learning concepts',
    'What is natural language processing?',
    'How does semantic search work?',
    'What are vector embeddings?',
    'How does document chunking work?',
    'What is LLM integration?',
];

// Sample responses for testing
const TEST_RESPONSES = [
    'This document is about the RAG system and its components.',
    'The main features include document processing, text chunking, and semantic search.',
    'The RAG system works by processing documents, creating embeddings, and using them for retrieval.',
    'Artificial intelligence is a field of computer science focused on creating intelligent machines.',
    'Machine learning is a subset of AI that enables computers to learn from data.',
    'Natural language processing deals with the interaction between computers and human language.',
    'Semantic search uses meaning and context to find relevant information.',
    'Vector embeddings are numerical representations of text in high-dimensional space.',
    'Document chunking breaks large documents into smaller, manageable pieces.',
    'LLM integration allows the system to generate responses using language models.',
];

// Sample embeddings (simplified for testing)
const TEST_EMBEDDINGS = [
    'query' => [0.1, 0.2, 0.3, 0.4, 0.5],
    'document' => [0.2, 0.3, 0.4, 0.5, 0.6],
    'chunk' => [0.15, 0.25, 0.35, 0.45, 0.55],
];

// Sample file paths for testing
const TEST_FILE_PATHS = [
    'txt' => '/test/documents/sample.txt',
    'md' => '/test/documents/sample.md',
    'html' => '/test/documents/sample.html',
    'pdf' => '/test/documents/sample.pdf',
    'docx' => '/test/documents/sample.docx',
    'doc' => '/test/documents/sample.doc',
];

// Sample user data for testing
const TEST_USERS = [
    'admin' => [
        'username' => 'admin',
        'email' => 'admin@example.com',
        'role' => 'admin',
        'permissions' => ['all'],
    ],
    'user' => [
        'username' => 'testuser',
        'email' => 'user@example.com',
        'role' => 'user',
        'permissions' => ['read', 'upload'],
    ],
    'viewer' => [
        'username' => 'viewer',
        'email' => 'viewer@example.com',
        'role' => 'viewer',
        'permissions' => ['read'],
    ],
];

// Sample API endpoints for testing
const TEST_API_ENDPOINTS = [
    'documents' => [
        'GET /api/v1/documents' => 'List documents',
        'POST /api/v1/documents' => 'Create document',
        'GET /api/v1/documents/{id}' => 'Get document',
        'PUT /api/v1/documents/{id}' => 'Update document',
        'DELETE /api/v1/documents/{id}' => 'Delete document',
        'POST /api/v1/documents/upload' => 'Upload file',
    ],
    'queries' => [
        'POST /api/v1/query' => 'Process query',
        'POST /api/v1/search' => 'Search documents',
        'GET /api/v1/queries' => 'Get query history',
        'POST /api/v1/queries/similar' => 'Find similar queries',
    ],
    'auth' => [
        'POST /api/v1/auth/login' => 'Login',
        'POST /api/v1/auth/logout' => 'Logout',
        'GET /api/v1/auth/me' => 'Get current user',
        'POST /api/v1/auth/extend' => 'Extend session',
    ],
];

// Sample error messages for testing
const TEST_ERROR_MESSAGES = [
    'validation' => [
        'required' => 'This field is required',
        'invalid_email' => 'Invalid email format',
        'too_short' => 'Value is too short',
        'too_long' => 'Value is too long',
    ],
    'database' => [
        'connection_failed' => 'Database connection failed',
        'query_failed' => 'Database query failed',
        'not_found' => 'Record not found',
        'duplicate' => 'Duplicate entry',
    ],
    'llm' => [
        'service_unavailable' => 'LLM service is unavailable',
        'timeout' => 'LLM request timeout',
        'invalid_response' => 'Invalid LLM response',
        'rate_limit' => 'Rate limit exceeded',
    ],
    'file' => [
        'upload_failed' => 'File upload failed',
        'invalid_format' => 'Invalid file format',
        'too_large' => 'File is too large',
        'processing_failed' => 'File processing failed',
    ],
];

// Sample configuration for testing
const TEST_CONFIG = [
    'app' => [
        'name' => 'RAG System Test',
        'env' => 'testing',
        'debug' => true,
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 5432,
        'database' => 'rag_test',
        'username' => 'test_user',
        'password' => 'test_password',
    ],
    'llm' => [
        'url' => 'http://localhost:8080',
        'timeout' => 30,
        'max_tokens' => 1000,
        'temperature' => 0.7,
    ],
    'rabbitmq' => [
        'host' => 'localhost',
        'port' => 5672,
        'username' => 'guest',
        'password' => 'guest',
    ],
    'vector_search' => [
        'limit' => 10,
        'similarity_threshold' => 0.8,
    ],
    'hybrid_search' => [
        'vector_top_k' => 20,
        'keyword_top_k' => 20,
    ],
    'chunking' => [
        'chunk_size' => 1000,
        'overlap' => 200,
    ],
];

// Helper function to get test data
function getTestData(string $type, ?string $key = null): mixed
{
    return match ($type) {
        'content' => $key ? TEST_DOCUMENT_CONTENT[$key] ?? null : TEST_DOCUMENT_CONTENT,
        'queries' => TEST_QUERIES,
        'responses' => TEST_RESPONSES,
        'embeddings' => $key ? TEST_EMBEDDINGS[$key] ?? null : TEST_EMBEDDINGS,
        'file_paths' => $key ? TEST_FILE_PATHS[$key] ?? null : TEST_FILE_PATHS,
        'users' => $key ? TEST_USERS[$key] ?? null : TEST_USERS,
        'endpoints' => $key ? TEST_API_ENDPOINTS[$key] ?? null : TEST_API_ENDPOINTS,
        'errors' => $key ? TEST_ERROR_MESSAGES[$key] ?? null : TEST_ERROR_MESSAGES,
        'config' => $key ? TEST_CONFIG[$key] ?? null : TEST_CONFIG,
        default => null,
    };
}
