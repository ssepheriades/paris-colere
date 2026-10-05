<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004104100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contact table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE contact (id UUID NOT NULL, controversy_id UUID DEFAULT NULL, name VARCHAR(128) NOT NULL, email VARCHAR(255) NOT NULL, message TEXT NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4C62E6383264675D ON contact (controversy_id)');
        $this->addSql('COMMENT ON COLUMN contact.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN contact.controversy_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN contact.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6383264675D FOREIGN KEY (controversy_id) REFERENCES controversy (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact DROP CONSTRAINT FK_4C62E6383264675D');
        $this->addSql('DROP TABLE contact');
    }
}
