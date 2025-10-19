<?php

declare(strict_types=1);

namespace RagSystem\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017175858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add full-text search support to document_chunks for hybrid search (BM25 + Vector)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_chunks ADD COLUMN search_vector tsvector');

        $this->addSql('CREATE INDEX idx_document_chunks_search_vector ON document_chunks USING gin(search_vector)');

        $this->addSql("UPDATE document_chunks SET search_vector = to_tsvector('russian', chunk_text)");

        // Function for automatic updating of search_vector
        $this->addSql("
            CREATE OR REPLACE FUNCTION document_chunks_search_vector_update() 
            RETURNS trigger AS \$\$
            BEGIN
                NEW.search_vector := to_tsvector('russian', NEW.chunk_text);
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Trigger for automatic update on INSERT/UPDATE
        $this->addSql('
            CREATE TRIGGER document_chunks_search_vector_trigger
            BEFORE INSERT OR UPDATE ON document_chunks
            FOR EACH ROW EXECUTE FUNCTION document_chunks_search_vector_update();
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS document_chunks_search_vector_trigger ON document_chunks');

        $this->addSql('DROP FUNCTION IF EXISTS document_chunks_search_vector_update()');

        $this->addSql('DROP INDEX IF EXISTS idx_document_chunks_search_vector');

        $this->addSql('ALTER TABLE document_chunks DROP COLUMN IF EXISTS search_vector');
    }
}
