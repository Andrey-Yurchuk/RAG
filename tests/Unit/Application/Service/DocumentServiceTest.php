<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Tests\Helpers\TestDataFactory;
use RagSystem\Application\Service\DocumentService;
use RagSystem\Application\Service\EmbeddingService;
use RagSystem\Application\Service\TextProcessingService;
use RagSystem\Application\Service\TaskService;
use RagSystem\Application\Service\TaskStatusService;
use RagSystem\Domain\Model\Document;
use RagSystem\Domain\Model\DocumentChunk;
use RagSystem\Domain\Repository\DocumentRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Exception;
use Mockery;

/**
 * Тесты для DocumentService
 * 
 * @covers \RagSystem\Application\Service\DocumentService
 * @covers \RagSystem\Domain\Model\Document
 * @covers \RagSystem\Domain\Model\DocumentChunk
 */
class DocumentServiceTest extends BaseTestCase
{
    private DocumentService $documentService;
    private DocumentRepositoryInterface $documentRepository;
    private EmbeddingService $embeddingService;
    private TextProcessingService $textProcessingService;
    private TaskService $taskService;
    private TaskStatusService $taskStatusService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = Mockery::mock(DocumentRepositoryInterface::class);
        $this->embeddingService = Mockery::mock(EmbeddingService::class);
        $this->textProcessingService = Mockery::mock(TextProcessingService::class);
        $this->taskService = Mockery::mock(TaskService::class);
        $this->taskStatusService = Mockery::mock(TaskStatusService::class);

        $this->documentService = new DocumentService(
            $this->documentRepository,
            $this->embeddingService,
            $this->textProcessingService,
            $this->taskService,
            $this->taskStatusService,
            $this->createMockLogger()
        );
    }

    /**
     * Тест создания документа синхронно
     * 
     * @covers \RagSystem\Application\Service\DocumentService::createDocument
     */
    public function testCreateDocument(): void
    {
        $title = 'Тестовый документ';
        $content = 'Содержимое тестового документа для обработки';
        $filePath = '/test/path/document.txt';
        $fileType = 'txt';

        $this->documentRepository->shouldReceive('save')->once()->andReturnNull();
        $this->textProcessingService->shouldReceive('chunkText')
            ->with($content)
            ->andReturn(['Фрагмент 1', 'Фрагмент 2']);
        $this->embeddingService->shouldReceive('generateEmbedding')
            ->twice()
            ->andReturn([0.1, 0.2, 0.3]);
        $this->documentRepository->shouldReceive('saveChunk')->twice()->andReturnNull();

        $document = $this->documentService->createDocument($title, $content, $filePath, $fileType);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals($title, $document->getTitle());
        $this->assertEquals($content, $document->getContent());
        $this->assertEquals($filePath, $document->getFilePath());
        $this->assertEquals($fileType, $document->getFileType());
        $this->assertCount(2, $document->getChunks());
    }

    /**
     * Тест создания документа асинхронно
     * 
     * @covers \RagSystem\Application\Service\DocumentService::createDocumentAsync
     */
    public function testCreateDocumentAsync(): void
    {
        $title = 'Асинхронный документ';
        $content = 'Содержимое для асинхронной обработки';
        $filePath = '/test/async/document.txt';
        $fileType = 'txt';

        $this->documentRepository->shouldReceive('save')->once()->andReturnNull();
        $this->taskService->shouldReceive('publishDocumentProcessingTask')
            ->once()
            ->withArgs(function ($documentId, $path, $type, $content) use ($filePath, $fileType) {
                return is_string($documentId) && 
                       $path === $filePath && 
                       $type === $fileType && 
                       $content === 'Содержимое для асинхронной обработки';
            })
            ->andReturnNull();

        $document = $this->documentService->createDocumentAsync($title, $content, $filePath, $fileType);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals($title, $document->getTitle());
        $this->assertEquals($content, $document->getContent());
        $this->assertEquals($filePath, $document->getFilePath());
        $this->assertEquals($fileType, $document->getFileType());
    }

    /**
     * Тест получения документа по ID
     * 
     * @covers \RagSystem\Application\Service\DocumentService::getDocument
     */
    public function testGetDocument(): void
    {
        $documentId = Uuid::uuid4();
        $expectedDocument = new Document('Найденный документ', 'Содержимое');

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->andReturn($expectedDocument);

        $document = $this->documentService->getDocument($documentId);

        $this->assertSame($expectedDocument, $document);
    }

    /**
     * Тест получения несуществующего документа
     */
    public function testGetNonExistentDocument(): void
    {
        $documentId = Uuid::uuid4();

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->andReturn(null);

        $document = $this->documentService->getDocument($documentId);

        $this->assertNull($document);
    }

    /**
     * Тест получения всех документов с пагинацией
     */
    public function testGetAllDocuments(): void
    {
        $documents = [
            new Document('Документ 1', 'Содержимое 1'),
            new Document('Документ 2', 'Содержимое 2'),
        ];

        $this->documentRepository->shouldReceive('findAll')
            ->with(10, 0)
            ->andReturn($documents);

        $result = $this->documentService->getAllDocuments(10, 0);

        $this->assertEquals($documents, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Тест получения всех документов с параметрами по умолчанию
     */
    public function testGetAllDocumentsWithDefaults(): void
    {
        $documents = [new Document('Документ', 'Содержимое')];

        $this->documentRepository->shouldReceive('findAll')
            ->with(10, 0)
            ->andReturn($documents);

        $result = $this->documentService->getAllDocuments();

        $this->assertEquals($documents, $result);
    }

    /**
     * Тест обновления существующего документа
     */
    public function testUpdateDocument(): void
    {
        $documentId = Uuid::uuid4();
        $existingDocument = new Document('Старый заголовок', 'Старое содержимое');
        $newTitle = 'Новый заголовок';
        $newContent = 'Новое содержимое документа';

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->andReturn($existingDocument);
        $this->documentRepository->shouldReceive('save')->once();
        $this->documentRepository->shouldReceive('deleteChunksByDocumentId')
            ->with($documentId)
            ->once();
        $this->textProcessingService->shouldReceive('chunkText')
            ->with($newContent)
            ->andReturn(['Новый фрагмент']);
        $this->embeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->andReturn([0.1, 0.2, 0.3]);
        $this->documentRepository->shouldReceive('saveChunk')->once();

        $updatedDocument = $this->documentService->updateDocument($documentId, $newTitle, $newContent);

        $this->assertInstanceOf(Document::class, $updatedDocument);
        $this->assertEquals($newTitle, $updatedDocument->getTitle());
        $this->assertEquals($newContent, $updatedDocument->getContent());
    }

    /**
     * Тест обновления несуществующего документа
     */
    public function testUpdateNonExistentDocument(): void
    {
        $documentId = Uuid::uuid4();
        $newTitle = 'Новый заголовок';
        $newContent = 'Новое содержимое';

        $this->documentRepository->shouldReceive('findById')
            ->with($documentId)
            ->andReturn(null);

        $result = $this->documentService->updateDocument($documentId, $newTitle, $newContent);

        $this->assertNull($result);
    }

    /**
     * Тест удаления документа
     */
    public function testDeleteDocument(): void
    {
        $documentId = Uuid::uuid4();

        $this->documentRepository->shouldReceive('delete')
            ->with($documentId)
            ->andReturn(true);

        $result = $this->documentService->deleteDocument($documentId);

        $this->assertTrue($result);
    }

    /**
     * Тест неудачного удаления документа
     */
    public function testDeleteDocumentFailure(): void
    {
        $documentId = Uuid::uuid4();

        $this->documentRepository->shouldReceive('delete')
            ->with($documentId)
            ->andReturn(false);

        $result = $this->documentService->deleteDocument($documentId);

        $this->assertFalse($result);
    }

    /**
     * Тест поиска похожих документов
     */
    public function testSearchSimilarDocuments(): void
    {
        $query = 'Поисковый запрос';
        $expectedResults = [
            ['chunk_text' => 'Найденный фрагмент 1', 'similarity_score' => 0.9],
            ['chunk_text' => 'Найденный фрагмент 2', 'similarity_score' => 0.8],
        ];

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($query)
            ->andReturn([0.1, 0.2, 0.3]);
        $this->documentRepository->shouldReceive('searchSimilarChunks')
            ->with([0.1, 0.2, 0.3], 10, 0.8)
            ->andReturn($expectedResults);

        $results = $this->documentService->searchSimilarDocuments($query);

        $this->assertEquals($expectedResults, $results);
        $this->assertCount(2, $results);
    }

    /**
     * Тест поиска похожих документов с кастомными параметрами
     */
    public function testSearchSimilarDocumentsWithCustomParams(): void
    {
        $query = 'Кастомный поиск';
        $limit = 5;
        $threshold = 0.9;

        $this->embeddingService->shouldReceive('generateEmbedding')
            ->with($query)
            ->andReturn([0.1, 0.2, 0.3]);
        $this->documentRepository->shouldReceive('searchSimilarChunks')
            ->with([0.1, 0.2, 0.3], $limit, $threshold)
            ->andReturn([]);

        $results = $this->documentService->searchSimilarDocuments($query, $limit, $threshold);

        $this->assertIsArray($results);
    }

    /**
     * Тест получения статуса обработки документа
     */
    public function testGetDocumentProcessingStatus(): void
    {
        $documentId = 'test-document-id';
        $expectedStatus = [
            'status' => 'processing',
            'progress' => 50,
            'message' => 'Обработка в процессе'
        ];

        $this->taskStatusService->shouldReceive('getDocumentProcessingStatus')
            ->with($documentId)
            ->andReturn($expectedStatus);

        $status = $this->documentService->getDocumentProcessingStatus($documentId);

        $this->assertEquals($expectedStatus, $status);
    }

    /**
     * Тест проверки завершения обработки документа
     */
    public function testIsDocumentProcessingCompleted(): void
    {
        $documentId = 'test-document-id';

        $this->taskStatusService->shouldReceive('getDocumentProcessingStatus')
            ->with($documentId)
            ->andReturn(['status' => 'completed']);

        $isCompleted = $this->documentService->isDocumentProcessingCompleted($documentId);

        $this->assertTrue($isCompleted);
    }

    /**
     * Тест проверки незавершенной обработки документа
     */
    public function testIsDocumentProcessingNotCompleted(): void
    {
        $documentId = 'test-document-id';

        $this->taskStatusService->shouldReceive('getDocumentProcessingStatus')
            ->with($documentId)
            ->andReturn(['status' => 'processing']);

        $isCompleted = $this->documentService->isDocumentProcessingCompleted($documentId);

        $this->assertFalse($isCompleted);
    }

    /**
     * Тест проверки ошибки обработки документа
     */
    public function testHasDocumentProcessingError(): void
    {
        $documentId = 'test-document-id';

        $this->taskStatusService->shouldReceive('getDocumentProcessingStatus')
            ->with($documentId)
            ->andReturn(['status' => 'failed']);

        $hasError = $this->documentService->hasDocumentProcessingError($documentId);

        $this->assertTrue($hasError);
    }

    /**
     * Тест проверки отсутствия ошибки обработки документа
     */
    public function testHasNoDocumentProcessingError(): void
    {
        $documentId = 'test-document-id';

        $this->taskStatusService->shouldReceive('getDocumentProcessingStatus')
            ->with($documentId)
            ->andReturn(['status' => 'completed']);

        $hasError = $this->documentService->hasDocumentProcessingError($documentId);

        $this->assertFalse($hasError);
    }

    /**
     * Тест обработки ошибки при создании фрагментов
     */
    public function testProcessDocumentChunksWithError(): void
    {
        $title = 'Документ с ошибкой';
        $content = 'Содержимое с ошибкой';

        $this->documentRepository->shouldReceive('save')->once()->andReturnNull();
        $this->textProcessingService->shouldReceive('chunkText')
            ->with($content)
            ->andThrow(new Exception('Ошибка обработки текста'));

        $document = $this->documentService->createDocument($title, $content);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEmpty($document->getChunks());
    }

    /**
     * Тест обработки пустого содержимого документа
     */
    public function testProcessDocumentWithEmptyContent(): void
    {
        $title = 'Пустой документ';
        $content = '';

        $this->documentRepository->shouldReceive('save')->once()->andReturnNull();
        $this->textProcessingService->shouldReceive('chunkText')
            ->with($content)
            ->andReturn([]);

        $document = $this->documentService->createDocument($title, $content);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertEmpty($document->getChunks());
    }

    /**
     * Тест обработки документа с одним фрагментом
     */
    public function testProcessDocumentWithSingleChunk(): void
    {
        $title = 'Документ с одним фрагментом';
        $content = 'Короткое содержимое';

        $this->documentRepository->shouldReceive('save')->once()->andReturnNull();
        $this->textProcessingService->shouldReceive('chunkText')
            ->with($content)
            ->andReturn(['Короткое содержимое']);
        $this->embeddingService->shouldReceive('generateEmbedding')
            ->once()
            ->andReturn([0.1, 0.2, 0.3]);
        $this->documentRepository->shouldReceive('saveChunk')->once()->andReturnNull();

        $document = $this->documentService->createDocument($title, $content);

        $this->assertInstanceOf(Document::class, $document);
        $this->assertCount(1, $document->getChunks());
        
        $chunk = $document->getChunks()[0];
        $this->assertInstanceOf(DocumentChunk::class, $chunk);
        $this->assertEquals('Короткое содержимое', $chunk->getChunkText());
        $this->assertEquals([0.1, 0.2, 0.3], $chunk->getEmbedding());
    }
}
