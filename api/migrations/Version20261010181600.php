<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010181600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store resolved embed metadata on source';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'source'",
        );
        $urlType = $this->connection->fetchOne(
            "SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'source' AND column_name = 'url'",
        );
        if ('text' !== $urlType) {
            $this->addSql('ALTER TABLE source ALTER url TYPE TEXT');
        }

        if (!\in_array('provider', $columns, true)) {
            $this->addSql("ALTER TABLE source ADD provider VARCHAR(32) DEFAULT 'link' NOT NULL");
        }
        if (!\in_array('external_id', $columns, true)) {
            $this->addSql('ALTER TABLE source ADD external_id VARCHAR(64) DEFAULT NULL');
        }
        if (!\in_array('title', $columns, true)) {
            $this->addSql('ALTER TABLE source ADD title VARCHAR(255) DEFAULT NULL');
        }
        if (!\in_array('thumbnail_url', $columns, true)) {
            $this->addSql('ALTER TABLE source ADD thumbnail_url VARCHAR(2048) DEFAULT NULL');
        }
        if (!\in_array('author_name', $columns, true)) {
            $this->addSql('ALTER TABLE source ADD author_name VARCHAR(255) DEFAULT NULL');
        }
        if (!\in_array('embed_text', $columns, true)) {
            $this->addSql('ALTER TABLE source ADD embed_text TEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE source DROP embed_text');
        $this->addSql('ALTER TABLE source DROP author_name');
        $this->addSql('ALTER TABLE source DROP thumbnail_url');
        $this->addSql('ALTER TABLE source DROP title');
        $this->addSql('ALTER TABLE source DROP external_id');
        $this->addSql('ALTER TABLE source DROP provider');
        $this->addSql('ALTER TABLE source ALTER url TYPE VARCHAR(255)');
    }
}
