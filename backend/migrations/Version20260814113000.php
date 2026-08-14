<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Generalise les favoris utilisateur en categories multi-types.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_favorites ADD category VARCHAR(32) NOT NULL DEFAULT 'product', ADD target_id INT NOT NULL DEFAULT 0");
        $this->addSql('UPDATE user_favorites SET category = \'product\', target_id = product_id');
        $this->addSql('ALTER TABLE user_favorites DROP INDEX UNIQ_5EDCA47EA76ED3954584665A');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT UNIQ_USER_FAVORITE_TARGET UNIQUE (user_id, category, target_id)');
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_5EDCA47E4584665A');
        $this->addSql('DROP INDEX IDX_5EDCA47E4584665A ON user_favorites');
        $this->addSql('ALTER TABLE user_favorites DROP product_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_favorites ADD product_id INT DEFAULT NULL');
        $this->addSql('UPDATE user_favorites SET product_id = target_id WHERE category = \'product\'');
        $this->addSql('DELETE FROM user_favorites WHERE category <> \'product\'');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_5EDCA47E4584665A FOREIGN KEY (product_id) REFERENCES catalog_products (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5EDCA47E4584665A ON user_favorites (product_id)');
        $this->addSql('ALTER TABLE user_favorites DROP INDEX UNIQ_USER_FAVORITE_TARGET');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT UNIQ_5EDCA47EA76ED3954584665A UNIQUE (user_id, product_id)');
        $this->addSql('ALTER TABLE user_favorites DROP category, DROP target_id');
    }
}
