<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721174500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Stripe tracking fields to training enrollments';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training_enrollments ADD stripe_session_id VARCHAR(190) DEFAULT NULL, ADD stripe_payment_intent_id VARCHAR(190) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_TRAINING_ENROLLMENT_STRIPE_SESSION ON training_enrollments (stripe_session_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_TRAINING_ENROLLMENT_STRIPE_SESSION ON training_enrollments');
        $this->addSql('ALTER TABLE training_enrollments DROP stripe_session_id, DROP stripe_payment_intent_id');
    }
}
