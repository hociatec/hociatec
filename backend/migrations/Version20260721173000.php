<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add paid training module with roadmaps, sessions and enrollments';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('CREATE TABLE trainings (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, slug VARCHAR(190) NOT NULL, short_description LONGTEXT DEFAULT NULL, objective LONGTEXT DEFAULT NULL, audience LONGTEXT DEFAULT NULL, duration_minutes INT NOT NULL, price_cents INT NOT NULL, available_formats JSON NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_TRAININGS_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('CREATE TABLE training_roadmap_items (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, position INT NOT NULL, title VARCHAR(220) NOT NULL, INDEX IDX_TRAINING_ROADMAP_TRAINING (training_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('CREATE TABLE training_sessions (id INT AUTO_INCREMENT NOT NULL, training_id INT NOT NULL, format VARCHAR(20) NOT NULL, starts_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ends_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', location VARCHAR(255) DEFAULT NULL, meeting_url VARCHAR(255) DEFAULT NULL, capacity INT NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_TRAINING_SESSION_TRAINING (training_id), INDEX IDX_TRAINING_SESSION_STARTS (starts_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('CREATE TABLE training_enrollments (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, user_id INT NOT NULL, status VARCHAR(30) NOT NULL, price_cents INT NOT NULL, paid_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_TRAINING_ENROLLMENT_SESSION (session_id), INDEX IDX_TRAINING_ENROLLMENT_USER (user_id), UNIQUE INDEX uniq_training_session_user (session_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->connection->executeStatement('ALTER TABLE training_roadmap_items ADD CONSTRAINT FK_TRAINING_ROADMAP_TRAINING FOREIGN KEY (training_id) REFERENCES trainings (id) ON DELETE CASCADE');
        $this->connection->executeStatement('ALTER TABLE training_sessions ADD CONSTRAINT FK_TRAINING_SESSION_TRAINING FOREIGN KEY (training_id) REFERENCES trainings (id) ON DELETE CASCADE');
        $this->connection->executeStatement('ALTER TABLE training_enrollments ADD CONSTRAINT FK_TRAINING_ENROLLMENT_SESSION FOREIGN KEY (session_id) REFERENCES training_sessions (id) ON DELETE CASCADE');
        $this->connection->executeStatement('ALTER TABLE training_enrollments ADD CONSTRAINT FK_TRAINING_ENROLLMENT_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->insert('trainings', [
            'title' => 'Prise en main ordinateur',
            'slug' => 'prise-en-main-ordinateur',
            'short_description' => 'Formation accompagnée pour gagner en autonomie sur les usages essentiels.',
            'objective' => 'Comprendre les bases, organiser ses fichiers et utiliser les outils courants avec confiance.',
            'audience' => 'Particuliers, indépendants ou petites structures souhaitant progresser avec un accompagnement direct.',
            'duration_minutes' => 120,
            'price_cents' => 9000,
            'available_formats' => json_encode(['onsite', 'remote'], JSON_THROW_ON_ERROR),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $trainingId = (int) $this->connection->lastInsertId();
        foreach ([
            'Vérifier l’environnement de travail',
            'Gérer fichiers, dossiers et sauvegardes simples',
            'Sécuriser les comptes et les accès principaux',
            'Répondre aux questions pratiques du participant',
        ] as $index => $title) {
            $this->connection->insert('training_roadmap_items', [
                'training_id' => $trainingId,
                'position' => $index + 1,
                'title' => $title,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE training_enrollments DROP FOREIGN KEY FK_TRAINING_ENROLLMENT_USER');
        $this->connection->executeStatement('ALTER TABLE training_enrollments DROP FOREIGN KEY FK_TRAINING_ENROLLMENT_SESSION');
        $this->connection->executeStatement('ALTER TABLE training_sessions DROP FOREIGN KEY FK_TRAINING_SESSION_TRAINING');
        $this->connection->executeStatement('ALTER TABLE training_roadmap_items DROP FOREIGN KEY FK_TRAINING_ROADMAP_TRAINING');
        $this->connection->executeStatement('DROP TABLE training_enrollments');
        $this->connection->executeStatement('DROP TABLE training_sessions');
        $this->connection->executeStatement('DROP TABLE training_roadmap_items');
        $this->connection->executeStatement('DROP TABLE trainings');
    }
}
