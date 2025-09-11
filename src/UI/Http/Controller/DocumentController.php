<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use Exception;
use InvalidArgumentException;
use RagSystem\Infrastructure\Http\Request;
use RagSystem\Infrastructure\Http\Response;
use RagSystem\Application\Service\DocumentService;
use RagSystem\Application\Service\TextProcessingService;
use Ramsey\Uuid\Uuid;
use Psr\Log\LoggerInterface;

class DocumentController
{
    private DocumentService $documentService;
    private TextProcessingService $textProcessingService;
    private LoggerInterface $logger;

    public function __construct(
        DocumentService       $documentService,
        TextProcessingService $textProcessingService,
        LoggerInterface       $logger
    )
    {
        $this->documentService = $documentService;
        $this->textProcessingService = $textProcessingService;
        $this->logger = $logger;
    }

    /**
     * Возвращает список документов с пагинацией
     */
    public function index(Request $request): Response
    {
        try {
            $limit = (int)($request->getQueryParam('limit') ?? 10);
            $offset = (int)($request->getQueryParam('offset') ?? 0);

            $documents = $this->documentService->getAllDocuments($limit, $offset);

            $data = array_map(function ($document) {
                return $document->toArray();
            }, $documents);

            return Response::json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($data)
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch documents', ['error' => $e->getMessage()]);
            return Response::internalServerError('Failed to fetch documents');
        }
    }

    /**
     * Возвращает документ по ID
     */
    public function show(Request $request, string $id): Response
    {
        try {
            $documentId = Uuid::fromString($id);
            $document = $this->documentService->getDocument($documentId);

            if (!$document) {
                return Response::notFound('Document not found');
            }

            return Response::json([
                'success' => true,
                'data' => $document->toArray()
            ]);
        } catch (InvalidArgumentException $e) {
            return Response::badRequest('Invalid document ID');
        } catch (Exception $e) {
            $this->logger->error('Failed to fetch document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to fetch document');
        }
    }

    /**
     * Создает новый документ
     */
    public function store(Request $request): Response
    {
        try {
            $data = $request->getBody();

            if (!isset($data['title']) || !isset($data['content'])) {
                return Response::badRequest('Title and content are required');
            }

            $title = $data['title'];
            $content = $data['content'];
            $filePath = $data['file_path'] ?? null;
            $fileType = $data['file_type'] ?? null;

            $document = $this->documentService->createDocument($title, $content, $filePath, $fileType);

            return Response::json([
                'success' => true,
                'data' => $document->toArray(),
                'message' => 'Document created successfully'
            ], 201);
        } catch (Exception $e) {
            $this->logger->error('Failed to create document', ['error' => $e->getMessage()]);
            return Response::internalServerError('Failed to create document');
        }
    }

    /**
     * Обновляет существующий документ
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $documentId = Uuid::fromString($id);
            $data = $request->getBody();

            if (!isset($data['title']) || !isset($data['content'])) {
                return Response::badRequest('Title and content are required');
            }

            $document = $this->documentService->updateDocument(
                $documentId,
                $data['title'],
                $data['content']
            );

            if (!$document) {
                return Response::notFound('Document not found');
            }

            return Response::json([
                'success' => true,
                'data' => $document->toArray(),
                'message' => 'Document updated successfully'
            ]);
        } catch (InvalidArgumentException $e) {
            return Response::badRequest('Invalid document ID');
        } catch (Exception $e) {
            $this->logger->error('Failed to update document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to update document');
        }
    }

    /**
     * Удаляет документ по ID
     */
    public function destroy(Request $request, string $id): Response
    {
        try {
            $documentId = Uuid::fromString($id);
            $success = $this->documentService->deleteDocument($documentId);

            if (!$success) {
                return Response::notFound('Document not found');
            }

            return Response::json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (InvalidArgumentException $e) {
            return Response::badRequest('Invalid document ID');
        } catch (Exception $e) {
            $this->logger->error('Failed to delete document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to delete document');
        }
    }
}
