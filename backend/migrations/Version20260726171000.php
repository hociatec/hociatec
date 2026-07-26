<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726171000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename trade-in price field to reflect the actual purchase price';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trade_in_requests CHANGE new_price_cents purchase_price_cents INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trade_in_requests CHANGE purchase_price_cents new_price_cents INT NOT NULL');
    }
}
