<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251029123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create quotes, quote_items and quote_services tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quotes (
            id INT AUTO_INCREMENT NOT NULL,
            number VARCHAR(30) NOT NULL,
            status VARCHAR(20) NOT NULL,
            customer_name VARCHAR(180) DEFAULT NULL,
            customer_email VARCHAR(180) DEFAULT NULL,
            customer_company VARCHAR(180) DEFAULT NULL,
            customer_address LONGTEXT DEFAULT NULL,
            global_discount_cents INT NOT NULL,
            shipping_cents INT NOT NULL,
            conditions LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_Quotes_Number (number),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE quote_items (
            id INT AUTO_INCREMENT NOT NULL,
            quote_id INT NOT NULL,
            item_type VARCHAR(20) NOT NULL,
            product_id INT DEFAULT NULL,
            service_id INT DEFAULT NULL,
            name VARCHAR(200) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            unit VARCHAR(30) DEFAULT NULL,
            quantity INT NOT NULL,
            unit_price_cents INT NOT NULL,
            vat_rate_bps INT NOT NULL,
            discount_cents INT NOT NULL,
            INDEX IDX_QITEM_QUOTE (quote_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE quote_items ADD CONSTRAINT FK_QITEM_QUOTE FOREIGN KEY (quote_id) REFERENCES quotes (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE quote_services (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(180) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            unit VARCHAR(30) DEFAULT NULL,
            price_cents INT NOT NULL,
            vat_rate_bps INT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_items DROP FOREIGN KEY FK_QITEM_QUOTE');
        $this->addSql('DROP TABLE quote_items');
        $this->addSql('DROP TABLE quote_services');
        $this->addSql('DROP TABLE quotes');
    }
}

