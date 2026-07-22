<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bookable time ranges to training sessions and scheduled slots to enrollments';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE training_sessions ADD daily_start_time TIME NOT NULL DEFAULT '08:00:00', ADD daily_end_time TIME NOT NULL DEFAULT '20:00:00'");
        $this->addSql('ALTER TABLE training_enrollments ADD scheduled_starts_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD scheduled_ends_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE training_enrollments e INNER JOIN training_sessions s ON s.id = e.session_id SET e.scheduled_starts_at = s.starts_at, e.scheduled_ends_at = s.ends_at');
        $this->addSql('ALTER TABLE training_enrollments MODIFY scheduled_starts_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', MODIFY scheduled_ends_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_TRAINING_ENROLLMENT_SLOT ON training_enrollments (session_id, scheduled_starts_at, scheduled_ends_at, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_TRAINING_ENROLLMENT_SLOT ON training_enrollments');
        $this->addSql('ALTER TABLE training_enrollments DROP scheduled_starts_at, DROP scheduled_ends_at');
        $this->addSql('ALTER TABLE training_sessions DROP daily_start_time, DROP daily_end_time');
    }
}
