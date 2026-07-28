<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user communication preferences and account notification events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD communication_preferences JSON DEFAULT NULL');
        $this->addSql('CREATE TABLE account_notification_events (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, notification_key VARCHAR(190) NOT NULL, title VARCHAR(180) NOT NULL, message LONGTEXT NOT NULL, target_url VARCHAR(500) NOT NULL, type VARCHAR(60) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_ACCOUNT_NOTIFICATION_USER (user_id), UNIQUE INDEX UNIQ_ACCOUNT_NOTIFICATION_KEY (notification_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_notification_events ADD CONSTRAINT FK_ACCOUNT_NOTIFICATION_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_notification_events DROP FOREIGN KEY FK_ACCOUNT_NOTIFICATION_USER');
        $this->addSql('DROP TABLE account_notification_events');
        $this->addSql('ALTER TABLE users DROP communication_preferences');
    }
}
