<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724040329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align Doctrine time_immutable metadata while preserving training session defaults.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training_sessions CHANGE daily_start_time daily_start_time TIME DEFAULT \'08:00:00\' NOT NULL COMMENT \'(DC2Type:time_immutable)\', CHANGE daily_end_time daily_end_time TIME DEFAULT \'20:00:00\' NOT NULL COMMENT \'(DC2Type:time_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training_sessions CHANGE daily_start_time daily_start_time TIME DEFAULT \'08:00:00\' NOT NULL, CHANGE daily_end_time daily_end_time TIME DEFAULT \'20:00:00\' NOT NULL');
    }
}
