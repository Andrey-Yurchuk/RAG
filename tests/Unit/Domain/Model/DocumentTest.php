<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Domain\Model;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Tests\Helpers\TestDataFactory;
use RagSystem\Domain\Model\Document;
use RagSystem\Domain\Model\DocumentChunk;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Тесты для модели Document
 * 
 * @covers \RagSystem\Domain\Model\Document
 * @covers \RagSystem\Domain\Model\DocumentChunk
 */
class DocumentTest extends BaseTestCase
{
    /**
     * Тест создания документа через конструктор
     */
    public function testDocumentCreation(): void
    {
        $title = 'Тестовый документ';
        $content = 'Содержимое тестового документа';
        $filePath = '/test/path/document.txt';
        $fileType = 'txt';

        $document = new Document($title, $content, $filePath, $fileType);

        $this->assertEquals($title, $document->getTitle());
        $this->assertEquals($content, $document->getContent());
        $this->assertEquals($filePath, $document->getFilePath());
        $this->assertEquals($fileType, $document->getFileType());
        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $document->getId());
        $this->assertInstanceOf(DateTimeImmutable::class, $document->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $document->getUpdatedAt());
    }

    /**
     * Тест создания документа с минимальными параметрами
     */
    public function testDocumentCreationWithMinimalParameters(): void
    {
        $title = 'Минимальный документ';
        $content = 'Минимальное содержимое';

        $document = new Document($title, $content);

        $this->assertEquals($title, $document->getTitle());
        $this->assertEquals($content, $document->getContent());
        $this->assertNull($document->getFilePath());
        $this->assertNull($document->getFileType());
    }

    /**
     * Тест создания документа из массива
     */
    public function testDocumentFromArray(): void
    {
        $data = TestDataFactory::createDocument([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Документ из массива',
            'content' => 'Содержимое из массива',
            'file_path' => '/test/array/document.txt',
            'file_type' => 'txt',
            'createdAt' => '2025-01-17 10:00:00',
            'updatedAt' => '2025-01-17 11:00:00',
        ]);

        $document = Document::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $document->getId()->toString());
        $this->assertEquals('Документ из массива', $document->getTitle());
        $this->assertEquals('Содержимое из массива', $document->getContent());
        $this->assertEquals('/test/array/document.txt', $document->getFilePath());
        $this->assertEquals('txt', $document->getFileType());
        $this->assertEquals('2025-01-17 10:00:00', $document->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-17 11:00:00', $document->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Тест создания документа из массива с минимальными данными
     */
    public function testDocumentFromArrayWithMinimalData(): void
    {
        $data = [
            'title' => 'Минимальный документ из массива',
            'content' => 'Минимальное содержимое из массива',
        ];

        $document = Document::fromArray($data);

        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $document->getId());
        $this->assertEquals('Минимальный документ из массива', $document->getTitle());
        $this->assertEquals('Минимальное содержимое из массива', $document->getContent());
        $this->assertNull($document->getFilePath());
        $this->assertNull($document->getFileType());
        $this->assertInstanceOf(DateTimeImmutable::class, $document->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $document->getUpdatedAt());
    }

    /**
     * Тест обновления содержимого документа
     */
    public function testUpdateContent(): void
    {
        $document = new Document('Тестовый документ', 'Старое содержимое');
        $originalUpdatedAt = $document->getUpdatedAt();

        usleep(1000);

        $newContent = 'Новое содержимое документа';
        $document->updateContent($newContent);

        $this->assertEquals($newContent, $document->getContent());
        $this->assertGreaterThan($originalUpdatedAt, $document->getUpdatedAt());
    }

    /**
     * Тест обновления заголовка документа
     */
    public function testUpdateTitle(): void
    {
        $document = new Document('Старый заголовок', 'Содержимое');
        $originalUpdatedAt = $document->getUpdatedAt();

        usleep(1000);

        $newTitle = 'Новый заголовок';
        $document->updateTitle($newTitle);

        $this->assertEquals($newTitle, $document->getTitle());
        $this->assertGreaterThan($originalUpdatedAt, $document->getUpdatedAt());
    }

    /**
     * Тест добавления фрагмента к документу
     */
    public function testAddChunk(): void
    {
        $document = new Document('Документ с фрагментами', 'Содержимое');
        $chunkData = TestDataFactory::createDocumentChunk();
        
        $chunk = new DocumentChunk(
            Uuid::fromString($chunkData['document_id']),
            $chunkData['chunk_text'],
            $chunkData['chunk_index'],
            $chunkData['embedding']
        );

        $this->assertEmpty($document->getChunks());

        $document->addChunk($chunk);

        $chunks = $document->getChunks();
        $this->assertCount(1, $chunks);
        $this->assertInstanceOf(DocumentChunk::class, $chunks[0]);
    }

    /**
     * Тест добавления нескольких фрагментов
     */
    public function testAddMultipleChunks(): void
    {
        $document = new Document('Документ с множественными фрагментами', 'Содержимое');
        
        $chunks = [];
        for ($i = 0; $i < 3; $i++) {
            $chunkData = TestDataFactory::createDocumentChunk([
                'chunk_text' => "Фрагмент номер {$i}",
                'chunk_index' => $i,
            ]);
            
            $chunk = new DocumentChunk(
                Uuid::fromString($chunkData['document_id']),
                $chunkData['chunk_text'],
                $chunkData['chunk_index'],
                $chunkData['embedding']
            );
            
            $chunks[] = $chunk;
            $document->addChunk($chunk);
        }

        $documentChunks = $document->getChunks();
        $this->assertCount(3, $documentChunks);
        
        foreach ($documentChunks as $index => $chunk) {
            $this->assertInstanceOf(DocumentChunk::class, $chunk);
            $this->assertEquals("Фрагмент номер {$index}", $chunk->getChunkText());
            $this->assertEquals($index, $chunk->getChunkIndex());
        }
    }

    /**
     * Тест преобразования документа в массив
     */
    public function testToArray(): void
    {
        $title = 'Документ для массива';
        $content = 'Содержимое для массива';
        $filePath = '/test/toarray/document.txt';
        $fileType = 'txt';

        $document = new Document($title, $content, $filePath, $fileType);
        $array = $document->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('file_path', $array);
        $this->assertArrayHasKey('file_type', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertEquals($title, $array['title']);
        $this->assertEquals($content, $array['content']);
        $this->assertEquals($filePath, $array['file_path']);
        $this->assertEquals($fileType, $array['file_type']);
        $this->assertIsString($array['id']);
        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
    }

    /**
     * Тест преобразования документа в массив с null значениями
     */
    public function testToArrayWithNullValues(): void
    {
        $document = new Document('Документ без файла', 'Содержимое');
        $array = $document->toArray();

        $this->assertNull($array['file_path']);
        $this->assertNull($array['file_type']);
    }

    /**
     * Тест неизменяемости ID документа
     */
    public function testDocumentIdImmutability(): void
    {
        $document = new Document('Тестовый документ', 'Содержимое');
        $originalId = $document->getId();

        $document->updateContent('Новое содержимое');
        $document->updateTitle('Новый заголовок');

        $this->assertEquals($originalId, $document->getId());
    }

    /**
     * Тест неизменяемости времени создания
     */
    public function testCreatedAtImmutability(): void
    {
        $document = new Document('Тестовый документ', 'Содержимое');
        $originalCreatedAt = $document->getCreatedAt();

        $document->updateContent('Новое содержимое');
        $document->updateTitle('Новый заголовок');

        $this->assertEquals($originalCreatedAt, $document->getCreatedAt());
    }

    /**
     * Тест валидации данных при создании из массива
     */
    public function testFromArrayDataValidation(): void
    {
        $data = [
            'title' => 'Документ с валидацией',
            'content' => 'Содержимое с валидацией',
            'filePath' => '/test/validation/document.txt',
            'fileType' => 'txt',
            'id' => 'invalid-uuid',
        ];

        $this->expectException(\InvalidArgumentException::class);
        Document::fromArray($data);
    }

    /**
     * Тест валидации времени при создании из массива
     */
    public function testFromArrayInvalidDateTime(): void
    {
        $data = [
            'title' => 'Документ с невалидным временем',
            'content' => 'Содержимое',
            'createdAt' => 'invalid-date',
        ];

        $this->expectException(\Exception::class);
        Document::fromArray($data);
    }
}
