<?php

declare(strict_types=1);

namespace RagSystem\UI\Http\Controller;

use Exception;
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
}
