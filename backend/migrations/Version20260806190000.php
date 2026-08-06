<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806190000 extends AbstractMigration
{
    private const SUPPORT_REQUEST_ORDER_FK = 'FK_CF335D6E8D9F6D38';

    public function getDescription(): string
    {
        return 'Replace support request order relation with orderId/orderNumber snapshot.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_requests ADD order_number VARCHAR(30) DEFAULT NULL');
        $this->addSql(
            'UPDATE support_requests s
             INNER JOIN orders o ON o.id = s.order_id
             SET s.order_number = o.number
             WHERE s.order_id IS NOT NULL',
        );

        foreach ($this->supportOrderForeignKeys() as $name) {
            $this->addSql(sprintf('ALTER TABLE support_requests DROP FOREIGN KEY %s', $name));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_requests DROP order_number');
        if (!$this->hasSupportOrderForeignKey()) {
            $this->addSql(sprintf(
                'ALTER TABLE support_requests ADD CONSTRAINT %s FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL',
                self::SUPPORT_REQUEST_ORDER_FK,
            ));
        }
    }

    /** @return list<string> */
    private function supportOrderForeignKeys(): array
    {
        $table = $this->connection->createSchemaManager()->listTableForeignKeys('support_requests');
        $keys = [];

        foreach ($table as $foreignKey) {
            if (!$foreignKey instanceof ForeignKeyConstraint) {
                continue;
            }

            if ('orders' === $foreignKey->getForeignTableName() && $foreignKey->getColumns() === ['order_id']) {
                $keys[] = $foreignKey->getName();
            }
        }

        return $keys;
    }

    private function supportOrderForeignKeyName(): ?string
    {
        foreach ($this->supportOrderForeignKeys() as $name) {
            return $name;
        }

        return null;
    }

    private function hasSupportOrderForeignKey(): bool
    {
        return null !== $this->supportOrderForeignKeyName();
    }
}
