<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add address complement to user shipping addresses';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_shipping_addresses');

        if (!$table->hasColumn('address_complement')) {
            $this->addSql('ALTER TABLE user_shipping_addresses ADD address_complement VARCHAR(180) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_shipping_addresses');

        if ($table->hasColumn('address_complement')) {
            $this->addSql('ALTER TABLE user_shipping_addresses DROP address_complement');
        }
    }
}
