<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_default column to user_shipping_addresses';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('user_shipping_addresses')) {
            $table = $schema->getTable('user_shipping_addresses');
            if (!$table->hasColumn('is_default')) {
                $this->addSql('ALTER TABLE user_shipping_addresses ADD is_default TINYINT(1) DEFAULT 0 NOT NULL');
                $this->addSql('UPDATE user_shipping_addresses SET is_default = 0');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user_shipping_addresses')) {
            $table = $schema->getTable('user_shipping_addresses');
            if ($table->hasColumn('is_default')) {
                $this->addSql('ALTER TABLE user_shipping_addresses DROP is_default');
            }
        }
    }
}
