<?php

require_once __DIR__ . '/../vendor/autoload.php';

use RagSystem\Infrastructure\DependencyInjection\Container;
use RagSystem\Infrastructure\DependencyInjection\ServiceProvider;
use RagSystem\Infrastructure\Service\RabbitMQService;
use RagSystem\Application\Service\DocumentService;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\TextProcessingService;
use RagSystem\Application\Service\TaskStatusService;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use RagSystem\Domain\Model\DocumentChunk;
use Ramsey\Uuid\Uuid;
use Psr\Log\LoggerInterface;
use ReflectionClass;


if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} else {
    error_log("Environment file not found");
}

try {
    $container = new Container();
    ServiceProvider::register($container);
    $logger = $container->get(LoggerInterface::class);
    $logger->info('Starting Simple RAG Worker');
} catch (Exception $e) {
    error_log("Failed to initialize container: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

$logger->info('Waiting for RabbitMQ to be ready...');
sleep(10);
$logger->info('Starting RabbitMQ connection...');

try {
    $rabbitMQHost = $_ENV['RABBITMQ_HOST'] ?? null;
    $rabbitMQPort = $_ENV['RABBITMQ_PORT'] ?? null;
    $rabbitMQUser = $_ENV['RABBITMQ_USER'] ?? null;
    $rabbitMQPassword = $_ENV['RABBITMQ_PASSWORD'] ?? null;
    
    if (!$rabbitMQHost || !$rabbitMQPort || !$rabbitMQUser || !$rabbitMQPassword) {
        throw new RuntimeException("RabbitMQ configuration is incomplete. Please check environment variables: RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASSWORD");
    }
    
    $logger->info("Connecting to RabbitMQ at {$rabbitMQHost}:{$rabbitMQPort}");
    
    $rabbitMQService = new RabbitMQService(
        $rabbitMQHost,
        (int)$rabbitMQPort,
        $rabbitMQUser,
        $rabbitMQPassword,
        $logger
    );
} catch (Exception $e) {
    error_log("Failed to connect to RabbitMQ: " . $e->getMessage());
    exit(1);
}

try {
    $documentService = $container->get(DocumentService::class);
    $embeddingService = $container->get(EmbeddingService::class);
    $textProcessingService = $container->get(TextProcessingService::class);
    $documentRepository = $container->get(DocumentRepositoryInterface::class);
    $taskStatusService = $container->get(TaskStatusService::class);
} catch (Exception $e) {
    error_log("Failed to load services: " . $e->getMessage());
    exit(1);
}

$documentCallback = function (array $message) use (
    $documentService,
    $embeddingService,
    $textProcessingService,
    $documentRepository,
    $taskStatusService,
    $logger
) {
    $logger->info('Processing document task', ['message' => $message]);

    try {
        $documentId = $message['document_id'];
        $filePath = $message['file_path'];
        $fileType = $message['file_type'];
        $content = $message['content'];

        $logger->debug('Document processing started', [
            'document_id' => $documentId,
            'content_length' => strlen($content)
        ]);

        $taskStatusService->updateDocumentProcessingStatus($documentId, 'processing', 0);

        $document = $documentRepository->findById(Uuid::fromString($documentId));
        if (!$document) {
            throw new Exception("Document not found: {$documentId}");
        }

        $originalFilePath = $document->getFilePath();
        $originalFileType = $document->getFileType();

        $document->updateContent($content);

        if ($originalFilePath && !$document->getFilePath()) {
            $reflection = new ReflectionClass($document);
            $filePathProperty = $reflection->getProperty('filePath');
            $filePathProperty->setAccessible(true);
            $filePathProperty->setValue($document, $originalFilePath);
        }
        
        if ($originalFileType && !$document->getFileType()) {
            $reflection = new ReflectionClass($document);
            $fileTypeProperty = $reflection->getProperty('fileType');
            $fileTypeProperty->setAccessible(true);
            $fileTypeProperty->setValue($document, $originalFileType);
        }
        
        $documentRepository->save($document);

        $chunks = $textProcessingService->chunkText($content);
        $totalChunks = count($chunks);
        
        $logger->info('Document chunked', [
            'document_id' => $documentId,
            'total_chunks' => $totalChunks
        ]);
        
        $taskStatusService->updateDocumentProcessingStatus($documentId, 'processing', 0, $totalChunks);

        $processedChunks = 0;
        
        foreach ($chunks as $index => $chunkText) {
            $logger->debug('Processing chunk', [
                'document_id' => $documentId,
                'chunk_index' => $index
            ]);
            
            $chunk = new DocumentChunk(
                $document->getId(),
                $chunkText,
                $index
            );

            try {
                $embedding = $embeddingService->generateEmbedding($chunkText);
                $chunk->setEmbedding($embedding);
                $logger->debug('Embedding generated', [
                    'document_id' => $documentId,
                    'chunk_index' => $index
                ]);
            } catch (Exception $e) {
                $logger->error('Failed to generate embedding', [
                    'document_id' => $documentId,
                    'chunk_index' => $index,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

            $documentRepository->saveChunk($chunk);
            $processedChunks++;

            $taskStatusService->updateDocumentProcessingStatus(
                $documentId,
                'processing',
                $processedChunks,
                $totalChunks
            );
        }

        $taskStatusService->updateDocumentProcessingStatus($documentId, 'completed', $totalChunks, $totalChunks);

        $logger->info('Document processing completed', [
            'document_id' => $documentId,
            'chunks_processed' => $totalChunks
        ]);

    } catch (Exception $e) {
        $taskStatusService->updateDocumentProcessingStatus($documentId, 'failed', 0, 0, $e->getMessage());
        $logger->error('Document processing failed', [
            'document_id' => $documentId,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
};

$logger->info('Starting to consume messages from document.processing queue');

try {
    $rabbitMQService->consume('document.processing', $documentCallback);
} catch (Exception $e) {
    error_log("Failed to consume messages: " . $e->getMessage());
    exit(1);
}
