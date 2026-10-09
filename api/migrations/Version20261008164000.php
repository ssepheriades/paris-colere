<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008164000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rewrite legacy controversy_item.type article to publication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE controversy_item SET type = 'publication' WHERE type = 'article'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE controversy_item SET type = 'article' WHERE type = 'publication'");
    }
}
