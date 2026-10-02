<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20261001182000 extends AbstractMigration
{
    /**
     * @var list<string>
     */
    private const TABLES = [
        'app_user',
        'controversy',
        'legal_entity',
        'party',
        'person',
        'controversy_item',
        'source',
        'person_legal_entity',
    ];

    /**
     * @var list<array{0: string, 1: string, 2: string, 3: bool, 4: bool}>
     */
    private const FOREIGN_KEYS = [
        ['controversy_item', 'controversy_id', 'controversy', false, false],
        ['source', 'controversy_item_id', 'controversy_item', true, false],
        ['person_legal_entity', 'person_id', 'person', false, false],
        ['person_legal_entity', 'legal_entity_id', 'legal_entity', false, false],
        ['controversy_item_person', 'controversy_item_id', 'controversy_item', false, true],
        ['controversy_item_person', 'person_id', 'person', false, true],
        ['person_party', 'person_id', 'person', false, true],
        ['person_party', 'party_id', 'party', false, true],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const PRIMARY_KEYS = [
        'app_user' => ['id'],
        'controversy' => ['id'],
        'controversy_item' => ['id'],
        'legal_entity' => ['id'],
        'party' => ['id'],
        'person' => ['id'],
        'person_legal_entity' => ['id'],
        'source' => ['id'],
        'controversy_item_person' => ['controversy_item_id', 'person_id'],
        'person_party' => ['person_id', 'party_id'],
    ];

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const INDEXES = [
        'idx_aab5d60e3264675d' => ['controversy_item', 'controversy_id'],
        'idx_5f8a7f73b6cd0a' => ['source', 'controversy_item_id'],
        'idx_1107e5e217bbb47' => ['person_legal_entity', 'person_id'],
        'idx_1107e5e6dec420c' => ['person_legal_entity', 'legal_entity_id'],
        'idx_a346cc9fb6cd0a' => ['controversy_item_person', 'controversy_item_id'],
        'idx_a346cc9f217bbb47' => ['controversy_item_person', 'person_id'],
        'idx_25a35124217bbb47' => ['person_party', 'person_id'],
        'idx_25a35124213c1059' => ['person_party', 'party_id'],
    ];

    /**
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const FOREIGN_KEY_NAMES = [
        'fk_aab5d60e3264675d' => ['controversy_item', 'controversy_id', 'controversy'],
        'fk_5f8a7f73b6cd0a' => ['source', 'controversy_item_id', 'controversy_item'],
        'fk_1107e5e217bbb47' => ['person_legal_entity', 'person_id', 'person'],
        'fk_1107e5e6dec420c' => ['person_legal_entity', 'legal_entity_id', 'legal_entity'],
        'fk_a346cc9fb6cd0a' => ['controversy_item_person', 'controversy_item_id', 'controversy_item'],
        'fk_a346cc9f217bbb47' => ['controversy_item_person', 'person_id', 'person'],
        'fk_25a35124217bbb47' => ['person_party', 'person_id', 'person'],
        'fk_25a35124213c1059' => ['person_party', 'party_id', 'party'],
    ];

    public function getDescription(): string
    {
        return 'Replace integer primary keys with UUID v7';
    }

    public function up(Schema $schema): void
    {
        $this->lock();

        foreach (self::TABLES as $table) {
            $this->addColumn($table, 'id_v7', 'UUID');
            $ids = $this->connection->fetchFirstColumn(sprintf('SELECT id FROM %s ORDER BY id', $table));
            foreach ($ids as $id) {
                $this->connection->executeStatement(
                    sprintf('UPDATE %s SET id_v7 = ? WHERE id = ?', $table),
                    [Uuid::v7()->toRfc4122(), $id],
                );
            }
            $this->connection->executeStatement(sprintf('ALTER TABLE %s ALTER id_v7 SET NOT NULL', $table));
        }

        foreach (self::FOREIGN_KEYS as [$table, $column, $parent, $nullable]) {
            $this->copyReference($table, $column, $parent, $column.'_v7', 'id_v7', $nullable);
        }

        $this->swap('id_v7', 'id');
    }

    public function down(Schema $schema): void
    {
        $this->lock();

        foreach (self::TABLES as $table) {
            $this->addColumn($table, 'id_int', 'INT');
            $this->connection->executeStatement(sprintf(
                'UPDATE %1$s SET id_int = numbered.n FROM (SELECT id, row_number() OVER (ORDER BY id) AS n FROM %1$s) numbered WHERE %1$s.id = numbered.id',
                $table,
            ));
            $this->connection->executeStatement(sprintf('ALTER TABLE %s ALTER id_int SET NOT NULL', $table));
        }

        foreach (self::FOREIGN_KEYS as [$table, $column, $parent, $nullable]) {
            $this->copyReference($table, $column, $parent, $column.'_int', 'id_int', $nullable);
        }

        $this->swap('id_int', 'id');

        foreach (self::TABLES as $table) {
            $this->connection->executeStatement(sprintf(
                'ALTER TABLE %s ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY',
                $table,
            ));
            $max = $this->connection->fetchOne(sprintf('SELECT MAX(id) FROM %s', $table));
            if (null === $max) {
                $this->connection->executeStatement(
                    sprintf("SELECT setval(pg_get_serial_sequence('%s', 'id'), 1, false)", $table),
                );
            } else {
                $this->connection->executeStatement(
                    sprintf("SELECT setval(pg_get_serial_sequence('%s', 'id'), ?)", $table),
                    [$max],
                );
            }
        }
    }

    private function lock(): void
    {
        $tables = array_merge(self::TABLES, ['controversy_item_person', 'person_party']);
        $this->connection->executeStatement('LOCK TABLE '.implode(', ', $tables).' IN ACCESS EXCLUSIVE MODE');
    }

    private function addColumn(string $table, string $column, string $type): void
    {
        $this->connection->executeStatement(sprintf('ALTER TABLE %s ADD %s %s', $table, $column, $type));
    }

    private function copyReference(string $table, string $column, string $parent, string $target, string $parentColumn, bool $nullable): void
    {
        $this->addColumn($table, $target, 'id_v7' === $parentColumn ? 'UUID' : 'INT');
        $this->connection->executeStatement(sprintf(
            'UPDATE %1$s AS child SET %2$s = parent.%3$s FROM %4$s AS parent WHERE child.%5$s = parent.id',
            $table,
            $target,
            $parentColumn,
            $parent,
            $column,
        ));
        if (!$nullable) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s ALTER %s SET NOT NULL', $table, $target));
        }
    }

    private function swap(string $fromSuffix, string $to): void
    {
        foreach (self::FOREIGN_KEY_NAMES as $name => [$table]) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s DROP CONSTRAINT %s', $table, $name));
        }

        foreach (self::PRIMARY_KEYS as $table => $columns) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s DROP CONSTRAINT %s_pkey', $table, $table));
        }

        foreach (self::TABLES as $table) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s DROP COLUMN id', $table));
            $this->connection->executeStatement(sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s', $table, $fromSuffix, $to));
        }

        foreach (self::FOREIGN_KEYS as [$table, $column]) {
            $this->connection->executeStatement(sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $column));
            $this->connection->executeStatement(sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s', $table, $column.substr($fromSuffix, 2), $column));
        }

        foreach (self::PRIMARY_KEYS as $table => $columns) {
            $this->connection->executeStatement(sprintf(
                'ALTER TABLE %s ADD PRIMARY KEY (%s)',
                $table,
                implode(', ', $columns),
            ));
        }

        foreach (self::INDEXES as $name => [$table, $column]) {
            $this->connection->executeStatement(sprintf('CREATE INDEX %s ON %s (%s)', $name, $table, $column));
        }

        foreach (self::FOREIGN_KEY_NAMES as $name => [$table, $column, $parent]) {
            $cascade = $this->cascades($table, $column) ? ' ON DELETE CASCADE' : '';
            $this->connection->executeStatement(sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id)%s',
                $table,
                $name,
                $column,
                $parent,
                $cascade,
            ));
        }
    }

    private function cascades(string $table, string $column): bool
    {
        foreach (self::FOREIGN_KEYS as [$fkTable, $fkColumn, , , $cascade]) {
            if ($fkTable === $table && $fkColumn === $column) {
                return $cascade;
            }
        }

        return false;
    }
}
