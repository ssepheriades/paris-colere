<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012103044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an optional theme icon and promise illustration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_promise ADD illustration VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE theme ADD icon VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_promise DROP illustration');
        $this->addSql('ALTER TABLE theme DROP icon');
    }
}
