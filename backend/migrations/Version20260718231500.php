<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718231500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lie un devis à sa commande convertie pour empêcher les doubles conversions.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $quotesTable = $schemaManager->introspectTable('quotes');

        if (!$quotesTable->hasColumn('converted_order_id')) {
            $this->addSql('ALTER TABLE quotes ADD converted_order_id INT DEFAULT NULL');
            $quotesTable = $schemaManager->introspectTable('quotes');
        }

        $this->addSql("UPDATE quotes q
            SET q.converted_order_id = (
                SELECT MIN(o.id)
                FROM orders o
                WHERE o.applied_promotion_slug = q.number
                  AND o.applied_promotion_name = CONCAT('Conversion devis ', q.number)
            )
            WHERE q.converted_order_id IS NULL");

        if (!$quotesTable->hasIndex('UNIQ_A1B588C5AB17FB50')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_A1B588C5AB17FB50 ON quotes (converted_order_id)');
        }

        if (!$this->hasForeignKey($quotesTable, 'FK_QUOTES_CONVERTED_ORDER')) {
            $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_QUOTES_CONVERTED_ORDER FOREIGN KEY (converted_order_id) REFERENCES orders (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $quotesTable = $schemaManager->introspectTable('quotes');

        if ($this->hasForeignKey($quotesTable, 'FK_QUOTES_CONVERTED_ORDER')) {
            $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_QUOTES_CONVERTED_ORDER');
        }
        if ($quotesTable->hasIndex('UNIQ_A1B588C5AB17FB50')) {
            $this->addSql('DROP INDEX UNIQ_A1B588C5AB17FB50 ON quotes');
        }
        if ($quotesTable->hasColumn('converted_order_id')) {
            $this->addSql('ALTER TABLE quotes DROP converted_order_id');
        }
    }

    private function hasForeignKey(Table $table, string $name): bool
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getName() === $name) {
                return true;
            }
        }

        return false;
    }
}
