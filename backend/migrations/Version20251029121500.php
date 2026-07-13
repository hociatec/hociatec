<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251029121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cart sessions and cart items tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE cart_sessions (
                id INT AUTO_INCREMENT NOT NULL,
                token VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_CART_SESSIONS_TOKEN (token),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE cart_items (
                id INT AUTO_INCREMENT NOT NULL,
                cart_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                INDEX IDX_CART_ITEMS_CART_ID (cart_id),
                INDEX IDX_CART_ITEMS_PRODUCT_ID (product_id),
                UNIQUE INDEX UNIQ_CART_ITEMS_CART_PRODUCT (cart_id, product_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE cart_items
                ADD CONSTRAINT FK_CART_ITEMS_CART
                    FOREIGN KEY (cart_id) REFERENCES cart_sessions (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'ALTER TABLE cart_items
                ADD CONSTRAINT FK_CART_ITEMS_PRODUCT
                    FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_CART_ITEMS_CART');
        $this->addSql('ALTER TABLE cart_items DROP FOREIGN KEY FK_CART_ITEMS_PRODUCT');
        $this->addSql('DROP TABLE cart_items');
        $this->addSql('DROP TABLE cart_sessions');
    }
}

