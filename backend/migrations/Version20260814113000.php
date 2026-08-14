<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Generalise les favoris utilisateur en categories multi-types.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('user_favorites');

        $columnSql = [];
        if (!$table->hasColumn('category')) {
            $columnSql[] = "ADD category VARCHAR(32) NOT NULL DEFAULT 'product'";
        }
        if (!$table->hasColumn('target_id')) {
            $columnSql[] = 'ADD target_id INT NOT NULL DEFAULT 0';
        }
        if ([] !== $columnSql) {
            $this->addSql('ALTER TABLE user_favorites '.implode(', ', $columnSql));
            $table = $schemaManager->introspectTable('user_favorites');
        }

        if ($table->hasColumn('product_id')) {
            $this->addSql("UPDATE user_favorites SET category = 'product', target_id = product_id WHERE product_id IS NOT NULL AND (target_id = 0 OR category <> 'product')");
        }

        $oldUniqueIndex = $this->findIndexByColumns($table, ['user_id', 'product_id'], true);
        if (null !== $oldUniqueIndex) {
            $this->addSql(sprintf('ALTER TABLE user_favorites DROP INDEX %s', $oldUniqueIndex));
        }

        if (!$this->hasIndex($table, 'UNIQ_USER_FAVORITE_TARGET')) {
            $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT UNIQ_USER_FAVORITE_TARGET UNIQUE (user_id, category, target_id)');
        }

        if ($table->hasColumn('product_id')) {
            foreach ($table->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['product_id']) {
                    $this->addSql(sprintf('ALTER TABLE user_favorites DROP FOREIGN KEY %s', $foreignKey->getName()));
                }
            }

            $productIndexes = $this->findIndexesByColumns($table, ['product_id']);
            foreach ($productIndexes as $indexName) {
                $this->addSql(sprintf('DROP INDEX %s ON user_favorites', $indexName));
            }

            $this->addSql('ALTER TABLE user_favorites DROP product_id');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_favorites ADD product_id INT DEFAULT NULL');
        $this->addSql('UPDATE user_favorites SET product_id = target_id WHERE category = \'product\'');
        $this->addSql('DELETE FROM user_favorites WHERE category <> \'product\'');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_5EDCA47E4584665A FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5EDCA47E4584665A ON user_favorites (product_id)');
        $this->addSql('ALTER TABLE user_favorites DROP INDEX UNIQ_USER_FAVORITE_TARGET');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT UNIQ_5EDCA47EA76ED3954584665A UNIQUE (user_id, product_id)');
        $this->addSql('ALTER TABLE user_favorites DROP category, DROP target_id');
    }

    private function hasIndex(\Doctrine\DBAL\Schema\Table $table, string $indexName): bool
    {
        foreach ($table->getIndexes() as $index) {
            if ($index->getName() === $indexName) {
                return true;
            }
        }

        return false;
    }

    private function findIndexByColumns(\Doctrine\DBAL\Schema\Table $table, array $columns, bool $uniqueOnly = false): ?string
    {
        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            if ($uniqueOnly && !$index->isUnique()) {
                continue;
            }

            if ($index->getColumns() === $columns) {
                return $index->getName();
            }
        }

        return null;
    }

    /** @return list<string> */
    private function findIndexesByColumns(\Doctrine\DBAL\Schema\Table $table, array $columns): array
    {
        $matches = [];

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary() || $index->isUnique()) {
                continue;
            }

            if ($index->getColumns() === $columns) {
                $matches[] = $index->getName();
            }
        }

        return $matches;
    }
}
