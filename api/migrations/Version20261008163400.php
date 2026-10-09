<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008163400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rewrite legacy controversy_item.type fait to fact';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE controversy_item SET type = 'fact' WHERE type = 'fait'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE controversy_item SET type = 'fait' WHERE type = 'fact'");
    }
}
