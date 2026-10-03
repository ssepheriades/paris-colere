<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002113500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add person short description, bio and wiki URL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE person ADD short_description VARCHAR(128) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE person ALTER short_description DROP DEFAULT');
        $this->addSql('ALTER TABLE person ADD bio TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD wiki_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person DROP short_description');
        $this->addSql('ALTER TABLE person DROP bio');
        $this->addSql('ALTER TABLE person DROP wiki_url');
    }
}
