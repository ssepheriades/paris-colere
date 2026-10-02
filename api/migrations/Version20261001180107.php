<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001180107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add photo and logo filenames';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE legal_entity ADD logo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE party ADD logo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD photo VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE legal_entity DROP logo');
        $this->addSql('ALTER TABLE party DROP logo');
        $this->addSql('ALTER TABLE person DROP photo');
    }
}
