<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add loyalty balances and awarded order points';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD loyalty_points_balance INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE orders ADD loyalty_points_awarded INT DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE orders SET loyalty_points_awarded = FLOOR(total_price_cents / 100) * 10 WHERE status IN ('confirmed', 'delivered')");
        $this->addSql('UPDATE users u SET loyalty_points_balance = COALESCE((SELECT SUM(o.loyalty_points_awarded) FROM orders o WHERE o.user_id = u.id), 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP loyalty_points_awarded');
        $this->addSql('ALTER TABLE users DROP loyalty_points_balance');
    }
}
