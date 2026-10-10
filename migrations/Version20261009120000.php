<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the feedback table that stores free-text feedback submitted by users.
 * This table is owned by this service (like exports); every other table belongs
 * to the FastAPI schema and is left untouched. The foreign key to users cascades,
 * so feedback is tied to the life of the user who sent it.
 */
final class Version20261009120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create feedback table for user-submitted feedback.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform,
            'This migration can only be run on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE feedback (
                id BIGSERIAL NOT NULL,
                user_id BIGINT NOT NULL,
                text TEXT NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
                PRIMARY KEY (id),
                CONSTRAINT fk_feedback_user FOREIGN KEY (user_id)
                    REFERENCES users (id) ON DELETE CASCADE
            )
            SQL);

        $this->addSql('CREATE INDEX idx_feedback_user_created ON feedback (user_id, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform,
            'This migration can only be run on PostgreSQL.',
        );

        $this->addSql('DROP TABLE feedback');
    }
}
