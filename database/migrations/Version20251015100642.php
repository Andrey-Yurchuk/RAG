<?php

declare(strict_types=1);

namespace RagSystem\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create default admin user for clean project installation
 */
final class Version20251015100642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create default admin user (username: admin, password: admin123) for clean project installation';
    }

    public function up(Schema $schema): void
    {
        // Create default admin user with correct password hash
        // Password: admin123 (hashed with PHP password_hash)
        // PLEASE CHANGE PASSWORD AFTER MIGRATION
        $passwordHash = '$2y$12$yHHzDnnz6cxHcwt3HZC1keqq32FK8Z4FLqIIiM3ccziifPV192xLm';
        
        $this->addSql("
            INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at) VALUES (
                'admin',
                'admin@rag-system.local',
                :password_hash,
                'admin',
                true,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
        ", ['password_hash' => $passwordHash]);
    }

    public function down(Schema $schema): void
    {
        // Remove admin user
        $this->addSql("DELETE FROM users WHERE username = 'admin'");
    }
}
