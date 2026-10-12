<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012135513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store a quantified result on campaign promises';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE campaign_promise SET result_type = NULL WHERE result_type IS NOT NULL AND result_type NOT IN ('absolute', 'percentage')");
        $this->addSql('ALTER TABLE campaign_promise ADD result_value NUMERIC(14, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_promise ADD objective NUMERIC(14, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_promise ADD result_label VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_promise DROP result_value');
        $this->addSql('ALTER TABLE campaign_promise DROP objective');
        $this->addSql('ALTER TABLE campaign_promise DROP result_label');
    }
}
