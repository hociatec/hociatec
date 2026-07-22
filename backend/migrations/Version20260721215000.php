<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721215000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create manageable training categories';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('CREATE TABLE training_categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(80) NOT NULL, position INT NOT NULL DEFAULT 0, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_TRAINING_CATEGORY_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        foreach ([
            ['Bases numériques', 'bases', 10],
            ['Sécurité et sauvegarde', 'securite', 20],
            ['Productivité', 'productivite', 30],
            ['Web et présence en ligne', 'web', 40],
            ['Intelligence artificielle', 'ia', 50],
            ['Entreprise', 'entreprise', 60],
            ['Général', 'general', 70],
        ] as [$name, $slug, $position]) {
            $this->connection->insert('training_categories', [
                'name' => $name,
                'slug' => $slug,
                'position' => $position,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('DROP TABLE training_categories');
    }
}
