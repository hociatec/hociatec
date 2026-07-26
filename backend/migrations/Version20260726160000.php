<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726160000 extends AbstractMigration
{
    public function getDescription(): string { return 'Create trade-in requests with customer estimates and admin offers.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE trade_in_requests (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, reference VARCHAR(30) NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) NOT NULL, category VARCHAR(80) NOT NULL, product_name VARCHAR(180) NOT NULL, brand VARCHAR(120) DEFAULT NULL, model VARCHAR(120) DEFAULT NULL, serial_number VARCHAR(120) DEFAULT NULL, condition_grade VARCHAR(30) NOT NULL, functional TINYINT(1) NOT NULL, has_accessories TINYINT(1) NOT NULL, has_proof_of_purchase TINYINT(1) NOT NULL, description LONGTEXT NOT NULL, catalog_product_id INT DEFAULT NULL, catalog_product_name VARCHAR(180) DEFAULT NULL, estimated_min_cents INT NOT NULL, estimated_max_cents INT NOT NULL, offer_cents INT DEFAULT NULL, admin_note LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, consent_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', offer_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_TRADE_IN_REFERENCE (reference), INDEX IDX_TRADE_IN_USER (user_id), INDEX IDX_TRADE_IN_STATUS (status), INDEX IDX_TRADE_IN_CREATED (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE trade_in_requests ADD CONSTRAINT FK_TRADE_IN_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void { $this->addSql('DROP TABLE trade_in_requests'); }
}
