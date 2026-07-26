<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove stock tracking from trade-in closures';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trade_in_requests DROP stock_product_id, DROP stock_added_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trade_in_requests ADD stock_product_id INT DEFAULT NULL, ADD stock_added_at DATETIME DEFAULT NULL');
    }
}
