<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the exports table that backs asynchronous data exports. This is the
 * first table this service owns; all other tables belong to the FastAPI schema
 * and are left untouched. The foreign key to childs cascades, so an export row
 * (and, separately, its stored file) is tied to the life of the child.
 */
final class Version20260909120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create exports table for asynchronous child data exports.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform,
            'This migration can only be run on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE exports (
                id BIGSERIAL NOT NULL,
                child_id BIGINT NOT NULL,
                requested_by BIGINT NOT NULL,
                type VARCHAR(32) NOT NULL,
                status VARCHAR(16) NOT NULL DEFAULT 'pending',
                date_from TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                date_to TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                s3_key VARCHAR(1024) DEFAULT NULL,
                error TEXT DEFAULT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW(),
                completed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id),
                CONSTRAINT fk_exports_child FOREIGN KEY (child_id)
                    REFERENCES childs (id) ON DELETE CASCADE
            )
            SQL);

        $this->addSql('CREATE INDEX idx_exports_child_created ON exports (child_id, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform,
            'This migration can only be run on PostgreSQL.',
        );

        $this->addSql('DROP TABLE exports');
    }
}
