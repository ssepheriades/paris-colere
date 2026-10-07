<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261006204736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_figure ADD is_visible BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE key_figure ADD controversy_id UUID NOT NULL');
        $this->addSql('ALTER TABLE key_figure ADD CONSTRAINT FK_246E52263264675D FOREIGN KEY (controversy_id) REFERENCES controversy (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_246E52263264675D ON key_figure (controversy_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_figure DROP CONSTRAINT FK_246E52263264675D');
        $this->addSql('DROP INDEX IDX_246E52263264675D');
        $this->addSql('ALTER TABLE key_figure DROP is_visible');
        $this->addSql('ALTER TABLE key_figure DROP controversy_id');
    }
}
