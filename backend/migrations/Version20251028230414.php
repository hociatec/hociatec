<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251028230414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catalog_categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(160) NOT NULL, description LONGTEXT DEFAULT NULL, is_visible TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8FD9B4B35E237E06 (name), UNIQUE INDEX UNIQ_8FD9B4B3989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE catalog_products (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, name VARCHAR(180) NOT NULL, slug VARCHAR(200) NOT NULL, sku VARCHAR(60) NOT NULL, short_description VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, price_cents INT NOT NULL, stock INT NOT NULL, is_published TINYINT(1) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, image_alt VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_816D8444989D9B62 (slug), UNIQUE INDEX UNIQ_816D8444F9038C4 (sku), INDEX IDX_816D844412469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE catalog_products ADD CONSTRAINT FK_816D844412469DE2 FOREIGN KEY (category_id) REFERENCES catalog_categories (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catalog_products DROP FOREIGN KEY FK_816D844412469DE2');
        $this->addSql('DROP TABLE catalog_categories');
        $this->addSql('DROP TABLE catalog_products');
    }
}
