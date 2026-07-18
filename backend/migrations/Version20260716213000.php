<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Stripe payment tracking metadata to checkout sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_checkout_sessions ADD stripe_payment_status VARCHAR(40) DEFAULT NULL, ADD last_stripe_event_type VARCHAR(80) DEFAULT NULL, ADD failure_code VARCHAR(120) DEFAULT NULL, ADD failure_message LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_checkout_sessions DROP stripe_payment_status, DROP last_stripe_event_type, DROP failure_code, DROP failure_message');
    }
}
