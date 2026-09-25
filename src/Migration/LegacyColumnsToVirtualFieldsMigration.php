<?php

declare(strict_types=1);

namespace Tbo\FormDelayProtection\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * Moves the spam protection settings of bundle versions < 0.10 from their
 * dedicated columns into the virtual "jsonData" column (Contao 5.7+).
 *
 * Since Contao 5.7 the DCA fields of this bundle no longer have an "sql"
 * definition and are therefore stored as virtual fields in the shared
 * "jsonData" column of tl_form. This migration copies the values of the
 * legacy columns over and drops the legacy columns afterwards.
 */
class LegacyColumnsToVirtualFieldsMigration extends AbstractMigration
{
    private const TARGET_TABLE = 'tl_form';

    private const TARGET_COLUMN = 'jsonData';

    private const LEGACY_COLUMNS = ['enableTimeBasedSpamProtection', 'minLoadTime'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return [] !== $this->getExistingLegacyColumns();
    }

    public function run(): MigrationResult
    {
        $legacyColumns = $this->getExistingLegacyColumns();

        if ([] === $legacyColumns) {
            return $this->createResult(true, 'No legacy columns found, nothing to migrate.');
        }

        // Make sure the virtual field storage exists before writing to it
        // (the migration can run before the schema update has added it)
        $this->ensureTargetColumn();

        $selectColumns = implode(', ', array_map(
            static fn (string $column): string => sprintf('`%s`', $column),
            array_merge(['id', self::TARGET_COLUMN], $legacyColumns),
        ));

        $rows = $this->connection->fetchAllAssociative(
            sprintf('SELECT %s FROM `%s`', $selectColumns, self::TARGET_TABLE)
        );

        $migrated = 0;

        foreach ($rows as $row) {
            $data = [];

            if (!empty($row[self::TARGET_COLUMN])) {
                $decoded = json_decode((string) $row[self::TARGET_COLUMN], true);

                if (\is_array($decoded)) {
                    $data = $decoded;
                }
            }

            $changed = false;

            foreach ($legacyColumns as $column) {
                // Values that already exist in jsonData always win
                if (\array_key_exists($column, $data) || null === $row[$column]) {
                    continue;
                }

                $data[$column] = $row[$column];
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $this->connection->update(
                self::TARGET_TABLE,
                [self::TARGET_COLUMN => $data],
                ['id' => $row['id']],
                [self::TARGET_COLUMN => Types::JSON],
            );

            ++$migrated;
        }

        foreach ($legacyColumns as $column) {
            $this->connection->executeStatement(
                sprintf('ALTER TABLE `%s` DROP COLUMN `%s`', self::TARGET_TABLE, $column)
            );
        }

        return $this->createResult(
            true,
            sprintf(
                'Migrated %d form(s) to the jsonData column and dropped the legacy columns (%s).',
                $migrated,
                implode(', ', $legacyColumns)
            )
        );
    }

    /**
     * Returns the legacy columns that still exist in the database.
     *
     * @return list<string>
     */
    private function getExistingLegacyColumns(): array
    {
        return array_values(array_filter(
            self::LEGACY_COLUMNS,
            fn (string $column): bool => \in_array(strtolower($column), $this->getExistingColumns(), true)
        ));
    }

    /**
     * Creates the "jsonData" column if it has not been created yet.
     */
    private function ensureTargetColumn(): void
    {
        if (\in_array(strtolower(self::TARGET_COLUMN), $this->getExistingColumns(), true)) {
            return;
        }

        $this->connection->executeStatement(
            sprintf('ALTER TABLE `%s` ADD `%s` JSON DEFAULT NULL', self::TARGET_TABLE, self::TARGET_COLUMN)
        );
    }

    /**
     * @return list<string>
     */
    private function getExistingColumns(): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TARGET_TABLE])) {
            return [];
        }

        return array_map(
            static fn ($column): string => strtolower($column->getName()),
            $schemaManager->listTableColumns(self::TARGET_TABLE)
        );
    }
}
