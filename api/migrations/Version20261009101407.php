<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009101407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source ADD provider VARCHAR(32) DEFAULT \'link\' NOT NULL');
        $this->addSql('ALTER TABLE source ADD external_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE source ADD title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE source ADD thumbnail_url VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE source ADD author_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE source ADD embed_text TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE source ALTER url TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE source DROP provider');
        $this->addSql('ALTER TABLE source DROP external_id');
        $this->addSql('ALTER TABLE source DROP title');
        $this->addSql('ALTER TABLE source DROP thumbnail_url');
        $this->addSql('ALTER TABLE source DROP author_name');
        $this->addSql('ALTER TABLE source DROP embed_text');
        $this->addSql('ALTER TABLE source ALTER url TYPE VARCHAR(255)');
    }
}
