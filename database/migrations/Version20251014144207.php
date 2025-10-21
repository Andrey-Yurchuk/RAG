<?php

declare(strict_types=1);

namespace RagSystem\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create users and user_sessions tables for authentication system
 */
final class Version20251014144207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and user_sessions tables for authentication system';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $usersTable = $schema->createTable('users');
        $usersTable->addColumn('id', 'integer', [
            'notnull' => true,
            'autoincrement' => true
        ]);
        $usersTable->addColumn('username', 'string', [
            'length' => 255,
            'notnull' => true
        ]);
        $usersTable->addColumn('email', 'string', [
            'length' => 255,
            'notnull' => true
        ]);
        $usersTable->addColumn('password_hash', 'string', [
            'length' => 255,
            'notnull' => true
        ]);
        $usersTable->addColumn('role', 'string', [
            'length' => 50,
            'notnull' => true,
            'default' => 'user'
        ]);
        $usersTable->addColumn('is_active', 'boolean', [
            'notnull' => true,
            'default' => true
        ]);
        $usersTable->addColumn('created_at', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP'
        ]);
        $usersTable->addColumn('updated_at', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP'
        ]);
        $usersTable->addColumn('last_login_at', 'datetime', [
            'notnull' => false
        ]);

        $usersTable->setPrimaryKey(['id']);
        $usersTable->addUniqueIndex(['username']);
        $usersTable->addUniqueIndex(['email']);

        // Create user_sessions table
        $sessionsTable = $schema->createTable('user_sessions');
        $sessionsTable->addColumn('id', 'integer', [
            'notnull' => true,
            'autoincrement' => true
        ]);
        $sessionsTable->addColumn('user_id', 'integer', [
            'notnull' => true
        ]);
        $sessionsTable->addColumn('session_token', 'string', [
            'length' => 255,
            'notnull' => true
        ]);
        $sessionsTable->addColumn('ip_address', 'string', [
            'length' => 45,
            'notnull' => false
        ]);
        $sessionsTable->addColumn('user_agent', 'text', [
            'notnull' => false
        ]);
        $sessionsTable->addColumn('expires_at', 'datetime', [
            'notnull' => true
        ]);
        $sessionsTable->addColumn('created_at', 'datetime', [
            'notnull' => true,
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        $sessionsTable->setPrimaryKey(['id']);
        $sessionsTable->addUniqueIndex(['session_token']);
        $sessionsTable->addIndex(['user_id']);
        $sessionsTable->addIndex(['expires_at']);

        // Add foreign key constraint
        $sessionsTable->addForeignKeyConstraint(
            'users',
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('user_sessions');
        $schema->dropTable('users');
    }

}
