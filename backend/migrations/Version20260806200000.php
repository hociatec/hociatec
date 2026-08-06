<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add trade-in requester user id snapshot.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE trade_in_requests ADD requester_user_id INT DEFAULT NULL');
        $this->addSql('UPDATE trade_in_requests SET requester_user_id = user_id WHERE user_id IS NOT NULL');
        $this->addSql('CREATE INDEX IDX_TRADE_IN_REQUESTER_USER_ID ON trade_in_requests (requester_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_TRADE_IN_REQUESTER_USER_ID ON trade_in_requests');
        $this->addSql('ALTER TABLE trade_in_requests DROP requester_user_id');
    }
}
