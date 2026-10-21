<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Existing controversy items stay full dates: the new column defaults to day.
 */
final class Version20261021162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add date precision to controversy items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE controversy_item ADD date_precision VARCHAR(16) DEFAULT 'day' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE controversy_item DROP date_precision');
    }
}
