<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_shipping_addresses table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user_shipping_addresses')) {
            $this->addSql('CREATE TABLE user_shipping_addresses (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(180) NOT NULL, address LONGTEXT NOT NULL, postal_code VARCHAR(20) NOT NULL, city VARCHAR(100) NOT NULL, INDEX IDX_3C9F9F8DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE user_shipping_addresses ADD CONSTRAINT FK_3C9F9F8DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('user_shipping_addresses')) {
            $this->addSql('DROP TABLE user_shipping_addresses');
        }
    }
}
