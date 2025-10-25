<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use RagSystem\Domain\Model\DocumentChunk;
use Ramsey\Uuid\Uuid;

/**
 * @covers \RagSystem\Domain\Model\DocumentChunk
 */
class DocumentChunkTest extends TestCase
{
    /**
     * Тест конструктора DocumentChunk
     */
    public function testDocumentChunkConstructor(): void
    {
        $documentId = Uuid::uuid4();
        $chunk = new DocumentChunk($documentId, 'chunk text', 1);
        
        $this->assertEquals($documentId, $chunk->getDocumentId());
        $this->assertEquals('chunk text', $chunk->getChunkText());
        $this->assertEquals(1, $chunk->getChunkIndex());
        $this->assertNull($chunk->getEmbedding());
        $this->assertFalse($chunk->hasEmbedding());
    }

    /**
     * Тест конструктора DocumentChunk с эмбеддингом
     */
    public function testDocumentChunkConstructorWithEmbedding(): void
    {
        $documentId = Uuid::uuid4();
        $embedding = [0.1, 0.2, 0.3];
        $chunk = new DocumentChunk($documentId, 'chunk text', 1, $embedding);
        
        $this->assertEquals($documentId, $chunk->getDocumentId());
        $this->assertEquals('chunk text', $chunk->getChunkText());
        $this->assertEquals(1, $chunk->getChunkIndex());
        $this->assertEquals($embedding, $chunk->getEmbedding());
        $this->assertTrue($chunk->hasEmbedding());
    }

    /**
     * Тест создания DocumentChunk из массива
     */
    public function testDocumentChunkFromArray(): void
    {
        $documentId = Uuid::uuid4();
        $chunkId = Uuid::uuid4();
        $data = [
            'id' => $chunkId->toString(),
            'document_id' => $documentId->toString(),
            'chunk_text' => 'chunk text',
            'chunk_index' => '1',
            'embedding' => [0.1, 0.2, 0.3],
            'createdAt' => '2023-01-01 10:00:00'
        ];
        
        $chunk = DocumentChunk::fromArray($data);
        
        $this->assertEquals($chunkId, $chunk->getId());
        $this->assertEquals($documentId, $chunk->getDocumentId());
        $this->assertEquals('chunk text', $chunk->getChunkText());
        $this->assertEquals(1, $chunk->getChunkIndex());
        $this->assertEquals([0.1, 0.2, 0.3], $chunk->getEmbedding());
        $this->assertEquals('2023-01-01 10:00:00', $chunk->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * Тест создания DocumentChunk из массива с значениями по умолчанию
     */
    public function testDocumentChunkFromArrayWithDefaults(): void
    {
        $documentId = Uuid::uuid4();
        $data = [
            'document_id' => $documentId->toString(),
            'chunk_text' => 'chunk text',
            'chunk_index' => '1'
        ];
        
        $chunk = DocumentChunk::fromArray($data);
        
        $this->assertNotNull($chunk->getId());
        $this->assertEquals($documentId, $chunk->getDocumentId());
        $this->assertEquals('chunk text', $chunk->getChunkText());
        $this->assertEquals(1, $chunk->getChunkIndex());
        $this->assertNull($chunk->getEmbedding());
        $this->assertNotNull($chunk->getCreatedAt());
    }

    /**
     * Тест установки эмбеддинга в DocumentChunk
     */
    public function testDocumentChunkSetEmbedding(): void
    {
        $documentId = Uuid::uuid4();
        $chunk = new DocumentChunk($documentId, 'chunk text', 1);
        
        $this->assertFalse($chunk->hasEmbedding());
        
        $embedding = [0.1, 0.2, 0.3];
        $chunk->setEmbedding($embedding);
        
        $this->assertEquals($embedding, $chunk->getEmbedding());
        $this->assertTrue($chunk->hasEmbedding());
    }

    /**
     * Тест установки null эмбеддинга в DocumentChunk
     */
    public function testDocumentChunkSetEmbeddingNull(): void
    {
        $documentId = Uuid::uuid4();
        $embedding = [0.1, 0.2, 0.3];
        $chunk = new DocumentChunk($documentId, 'chunk text', 1, $embedding);
        
        $this->assertTrue($chunk->hasEmbedding());
        
        $chunk->setEmbedding(null);
        
        $this->assertNull($chunk->getEmbedding());
        $this->assertFalse($chunk->hasEmbedding());
    }

    /**
     * Тест преобразования DocumentChunk в массив
     */
    public function testDocumentChunkToArray(): void
    {
        $documentId = Uuid::uuid4();
        $embedding = [0.1, 0.2, 0.3];
        $chunk = new DocumentChunk($documentId, 'chunk text', 1, $embedding);
        
        $array = $chunk->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertEquals($documentId->toString(), $array['document_id']);
        $this->assertEquals('chunk text', $array['chunk_text']);
        $this->assertEquals(1, $array['chunk_index']);
        $this->assertEquals($embedding, $array['embedding']);
        $this->assertArrayHasKey('created_at', $array);
    }

    /**
     * Тест преобразования DocumentChunk в массив с null эмбеддингом
     */
    public function testDocumentChunkToArrayWithNullEmbedding(): void
    {
        $documentId = Uuid::uuid4();
        $chunk = new DocumentChunk($documentId, 'chunk text', 1);
        
        $array = $chunk->toArray();
        
        $this->assertNull($array['embedding']);
    }

    /**
     * Тест геттеров DocumentChunk
     */
    public function testDocumentChunkGetters(): void
    {
        $documentId = Uuid::uuid4();
        $embedding = [0.1, 0.2, 0.3];
        $chunk = new DocumentChunk($documentId, 'chunk text', 1, $embedding);
        
        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $chunk->getId());
        $this->assertEquals($documentId, $chunk->getDocumentId());
        $this->assertEquals('chunk text', $chunk->getChunkText());
        $this->assertEquals(1, $chunk->getChunkIndex());
        $this->assertEquals($embedding, $chunk->getEmbedding());
        $this->assertInstanceOf(\DateTimeImmutable::class, $chunk->getCreatedAt());
    }
}