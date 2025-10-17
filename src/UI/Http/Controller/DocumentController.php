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
        DocumentService $documentService,
        TextProcessingService $textProcessingService,
        LoggerInterface $logger
    ) {
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

    /**
     * Загружает и обрабатывает файл для создания документа
     */
    public function upload(Request $request): Response
    {
        $this->logger->info('File upload request received', [
            'content_type' => $request->getHeader('Content-Type'),
            'method' => $request->getMethod(),
            'files_count' => count($request->getFiles())
        ]);

        try {
            $uploadedFile = $request->getFile('file');

            if (!$uploadedFile) {
                $this->logger->error('No file uploaded', ['request_files' => $request->getFiles()]);
                return Response::badRequest('No file uploaded');
            }

            $this->logger->info('File upload details', [
                'file_name' => $uploadedFile['name'],
                'file_size' => $uploadedFile['size'],
                'file_type' => $uploadedFile['type'],
                'file_error' => $uploadedFile['error']
            ]);

            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                $this->logger->error('File upload error', [
                    'error_code' => $uploadedFile['error'],
                    'error_message' => $this->getUploadErrorMessage($uploadedFile['error'])
                ]);
                return Response::badRequest(
                    'File upload failed: ' . $this->getUploadErrorMessage($uploadedFile['error'])
                );
            }

            $filePath = $uploadedFile['tmp_name'];
            $fileName = $uploadedFile['name'];

            $this->logger->info('File name debugging', [
                'original_name' => $uploadedFile['name'],
                'file_name' => $fileName,
                'pathinfo_result' => pathinfo($fileName),
                'extension_raw' => pathinfo($fileName, PATHINFO_EXTENSION)
            ]);

            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Handle case where file extension is empty
            if (empty($fileType)) {
                // Try to detect file type by content for files without extension
                $mimeType = mime_content_type($filePath);
                $this->logger->info('File without extension detected', [
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                    'original_name' => $uploadedFile['name']
                ]);

                // If it's a text file, treat it as .txt
                if (strpos($mimeType, 'text/') === 0 || $mimeType === 'application/octet-stream') {
                    $fileType = 'txt';
                    $this->logger->info('File without extension treated as text file', [
                        'file_name' => $fileName,
                        'assigned_type' => $fileType
                    ]);
                } else {
                    $this->logger->error('File has no extension and unknown MIME type', [
                        'file_name' => $fileName,
                        'mime_type' => $mimeType,
                        'original_name' => $uploadedFile['name'],
                        'pathinfo_debug' => pathinfo($fileName)
                    ]);
                    return Response::badRequest(
                        'File must have a valid extension (txt, md, html, pdf, docx, doc) ' .
                        'or be a recognizable text file'
                    );
                }
            }

            // Check if file type is supported
            $supportedTypes = ['txt', 'md', 'html', 'pdf', 'docx', 'doc'];
            if (!in_array($fileType, $supportedTypes)) {
                $this->logger->error('Unsupported file type', [
                    'file_type' => $fileType,
                    'file_name' => $fileName,
                    'supported_types' => $supportedTypes
                ]);
                return Response::badRequest(
                    'Unsupported file type: ' . $fileType . '. Supported types: ' . implode(', ', $supportedTypes)
                );
            }

            $this->logger->info('Processing file', [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_type' => $fileType
            ]);

            // Extract text from file
            $content = $this->textProcessingService->extractTextFromFile($filePath, $fileType);

            if (empty($content)) {
                $this->logger->error('Empty content extracted from file', [
                    'file_name' => $fileName,
                    'file_type' => $fileType
                ]);
                return Response::badRequest('Could not extract text from file');
            }

            $this->logger->info('Text extracted successfully', [
                'file_name' => $fileName,
                'content_length' => strlen($content)
            ]);

            // Move file to permanent location
            $targetPath = '/app/storage/uploads/' . uniqid('', true) . '_' . $fileName;

            // Create uploads directory if it doesn't exist
            $uploadsDir = dirname($targetPath);
            if (!is_dir($uploadsDir)) {
                if (!mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadsDir));
                }
            }

            move_uploaded_file($filePath, $targetPath);

            $document = $this->documentService->createDocumentAsync(
                $fileName,
                $content,
                $targetPath,
                $fileType
            );

            $this->logger->info('Document created and queued for processing', [
                'document_id' => $document->getId()->toString(),
                'file_name' => $fileName
            ]);

            return Response::json([
                'success' => true,
                'data' => $document->toArray(),
                'message' => 'File uploaded and queued for processing',
                'processing_status' => 'pending'
            ], 201);
        } catch (Exception $e) {
            $this->logger->error('Failed to upload file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Response::internalServerError('Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Возвращает описание ошибки загрузки файла по коду
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File too large (exceeds upload_max_filesize)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large (exceeds MAX_FILE_SIZE)';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Возвращает статус обработки документа
     */
    public function processingStatus(Request $request, string $id): Response
    {
        try {
            $documentId = Uuid::fromString($id);
            $document = $this->documentService->getDocument($documentId);

            if (!$document) {
                return Response::notFound('Document not found');
            }

            $status = $this->documentService->getDocumentProcessingStatus($id);

            return Response::json([
                'success' => true,
                'data' => [
                    'document_id' => $id,
                    'status' => $status['status'],
                    'processed' => (int)($status['processed'] ?? 0),
                    'total' => (int)($status['total'] ?? 0),
                    'percentage' => (float)($status['percentage'] ?? 0),
                    'updated_at' => (int)($status['updated_at'] ?? 0),
                    'error' => $status['error'] ?? null
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return Response::badRequest('Invalid document ID');
        } catch (Exception $e) {
            $this->logger->error('Failed to get processing status', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);
            return Response::internalServerError('Failed to get processing status');
        }
    }
}
