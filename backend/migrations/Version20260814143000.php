<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harden account notifications uniqueness per user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ACCOUNT_NOTIFICATION_KEY ON account_notification_events');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACCOUNT_NOTIFICATION_USER_KEY ON account_notification_events (user_id, notification_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_ACCOUNT_NOTIFICATION_USER_KEY ON account_notification_events');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACCOUNT_NOTIFICATION_KEY ON account_notification_events (notification_key)');
    }
}
