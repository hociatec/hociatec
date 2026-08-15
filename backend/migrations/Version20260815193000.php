<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add paid rental extension links and rental return planning fields';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('ALTER TABLE order_items ADD rental_origin_order_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_extension_order_id INT DEFAULT NULL');
        $this->addSql("ALTER TABLE order_items ADD rental_return_status VARCHAR(30) NOT NULL DEFAULT 'none'");
        $this->addSql('ALTER TABLE order_items ADD rental_return_mode VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_return_requested_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items ADD rental_return_requested_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE order_items ADD rental_return_completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_order_items_rental_origin ON order_items (rental_origin_order_item_id)');
        $this->addSql('CREATE INDEX idx_order_items_rental_extension_order ON order_items (rental_extension_order_id)');
        $this->addSql('CREATE INDEX idx_order_items_rental_return ON order_items (rental_return_status, rental_return_requested_date)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql('DROP INDEX idx_order_items_rental_return ON order_items');
        $this->addSql('DROP INDEX idx_order_items_rental_extension_order ON order_items');
        $this->addSql('DROP INDEX idx_order_items_rental_origin ON order_items');
        $this->addSql('ALTER TABLE order_items DROP rental_return_completed_at');
        $this->addSql('ALTER TABLE order_items DROP rental_return_requested_at');
        $this->addSql('ALTER TABLE order_items DROP rental_return_requested_date');
        $this->addSql('ALTER TABLE order_items DROP rental_return_mode');
        $this->addSql('ALTER TABLE order_items DROP rental_return_status');
        $this->addSql('ALTER TABLE order_items DROP rental_extension_order_id');
        $this->addSql('ALTER TABLE order_items DROP rental_origin_order_item_id');
    }
}
