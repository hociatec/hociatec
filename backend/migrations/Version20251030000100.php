<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove address fields from users; addresses moved to user_shipping_addresses';
    }

    public function up(Schema $schema): void
    {
        // assuming default table and column names
        if ($schema->hasTable('users')) {
            $table = $schema->getTable('users');
            if ($table->hasColumn('address')) {
                $this->addSql('ALTER TABLE users DROP COLUMN address');
            }
            if ($table->hasColumn('postal_code')) {
                $this->addSql('ALTER TABLE users DROP COLUMN postal_code');
            }
            if ($table->hasColumn('city')) {
                $this->addSql('ALTER TABLE users DROP COLUMN city');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // re-add columns as nullable for rollback
        $this->addSql("ALTER TABLE users ADD address VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE users ADD postal_code VARCHAR(20) DEFAULT NULL");
        $this->addSql("ALTER TABLE users ADD city VARCHAR(100) DEFAULT NULL");
    }
}

