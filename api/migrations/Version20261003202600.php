<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003202600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add theme short description';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE theme ADD short_description VARCHAR(128) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE theme ALTER short_description DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE theme DROP short_description');
    }
}
