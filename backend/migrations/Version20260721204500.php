<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721204500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recalculate existing training enrollment slot end dates from training duration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE training_enrollments e INNER JOIN training_sessions s ON s.id = e.session_id INNER JOIN trainings t ON t.id = s.training_id SET e.scheduled_ends_at = DATE_ADD(e.scheduled_starts_at, INTERVAL GREATEST(t.duration_minutes, 1) MINUTE)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE training_enrollments e INNER JOIN training_sessions s ON s.id = e.session_id SET e.scheduled_ends_at = s.ends_at');
    }
}
