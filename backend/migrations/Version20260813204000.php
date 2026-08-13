<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813204000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove professional billing data from all user shipping addresses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user_shipping_addresses SET company = NULL, company_siren = NULL, company_vat_number = NULL, purchase_order_number = NULL');
    }

    public function down(Schema $schema): void
    {
        // Irreversible data purge.
    }
}
