<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251123133500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lie les sessions panier aux utilisateurs (colonne user_id nullable + FK).';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('cart_sessions')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE cart_sessions ADD user_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_CART_SESSIONS_USER ON cart_sessions (user_id)');
            $this->addSql('ALTER TABLE cart_sessions ADD CONSTRAINT FK_CART_SESSIONS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('cart_sessions')->hasForeignKey('FK_CART_SESSIONS_USER')) {
            $this->addSql('ALTER TABLE cart_sessions DROP FOREIGN KEY FK_CART_SESSIONS_USER');
        }

        if ($schema->getTable('cart_sessions')->hasIndex('IDX_CART_SESSIONS_USER')) {
            $this->addSql('DROP INDEX IDX_CART_SESSIONS_USER ON cart_sessions');
        }

        if ($schema->getTable('cart_sessions')->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE cart_sessions DROP user_id');
        }
    }
}
