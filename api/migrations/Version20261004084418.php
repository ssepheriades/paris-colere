<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004084418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_visible to person and controversy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE controversy ADD is_visible BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE person ADD is_visible BOOLEAN DEFAULT true NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE controversy DROP is_visible');
        $this->addSql('ALTER TABLE person DROP is_visible');
    }
}
