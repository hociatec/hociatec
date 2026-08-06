<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table user_favorites pour gerer les favoris des utilisateurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_favorites (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_5EDCA47EA76ED395 (user_id),
            INDEX IDX_5EDCA47E4584665A (product_id),
            UNIQUE INDEX UNIQ_5EDCA47EA76ED3954584665A (user_id, product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_5EDCA47EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_5EDCA47E4584665A FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_5EDCA47EA76ED395');
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_5EDCA47E4584665A');
        $this->addSql('DROP TABLE user_favorites');
    }
}
