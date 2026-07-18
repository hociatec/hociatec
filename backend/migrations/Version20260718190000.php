<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute exploitation admin: SAV, remboursements et mouvements de stock.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE support_requests (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, order_id INT DEFAULT NULL, status VARCHAR(40) NOT NULL, reason VARCHAR(80) NOT NULL, subject VARCHAR(180) NOT NULL, message LONGTEXT DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, attachments JSON NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_CF335D6E9395C3F3 (customer_id), INDEX IDX_CF335D6E8D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE support_requests ADD CONSTRAINT FK_CF335D6E9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_requests ADD CONSTRAINT FK_CF335D6E8D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL');

        $this->addSql("CREATE TABLE stock_movements (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, actor_id INT DEFAULT NULL, delta INT NOT NULL, stock_before INT NOT NULL, stock_after INT NOT NULL, reason VARCHAR(60) NOT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A0BE93C94584665A (product_id), INDEX IDX_A0BE93C910DAF24A (actor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C94584665A FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C910DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');

        $this->addSql("CREATE TABLE refund_requests (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, actor_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, amount_cents INT NOT NULL, currency_code VARCHAR(3) NOT NULL, status VARCHAR(40) NOT NULL, reason VARCHAR(120) DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, stripe_refund_id VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_A6AE4528D9F6D38 (order_id), INDEX IDX_A6AE45210DAF24A (actor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE refund_requests ADD CONSTRAINT FK_A6AE4528D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE refund_requests ADD CONSTRAINT FK_A6AE45210DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE support_requests');
        $this->addSql('DROP TABLE stock_movements');
        $this->addSql('DROP TABLE refund_requests');
    }
}
