<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250228150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create appointment module tables (prestations, working days, appointments)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE appointment_prestations (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, duration_minutes INT NOT NULL, price_cents INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment_working_days (id INT AUTO_INCREMENT NOT NULL, day_of_week SMALLINT NOT NULL, is_working_day TINYINT(1) NOT NULL, start_time TIME DEFAULT NULL COMMENT \'(DC2Type:time_immutable)\', end_time TIME DEFAULT NULL COMMENT \'(DC2Type:time_immutable)\', breaks JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_working_day_configuration_day (day_of_week), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointments (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, prestation_id INT NOT NULL, start_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_APPOINTMENTS_USER (user_id), INDEX IDX_APPOINTMENTS_PRESTATION (prestation_id), INDEX idx_appointments_start_at (start_at), INDEX idx_appointments_end_at (end_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_APPOINTMENTS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointments ADD CONSTRAINT FK_APPOINTMENTS_PRESTATION FOREIGN KEY (prestation_id) REFERENCES appointment_prestations (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_APPOINTMENTS_USER');
        $this->addSql('ALTER TABLE appointments DROP FOREIGN KEY FK_APPOINTMENTS_PRESTATION');
        $this->addSql('DROP TABLE appointments');
        $this->addSql('DROP TABLE appointment_working_days');
        $this->addSql('DROP TABLE appointment_prestations');
    }
}
