<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006213149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename key_figure.unit to sub_label';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE key_figure RENAME COLUMN unit TO sub_label');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE key_figure RENAME COLUMN sub_label TO unit');
    }
}
