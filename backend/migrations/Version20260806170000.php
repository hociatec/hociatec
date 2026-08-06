<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store quote converted order snapshot number and keep conversion id as scalar.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes ADD converted_order_number VARCHAR(40) DEFAULT NULL');
        $this->addSql(
            'UPDATE quotes q
             INNER JOIN orders o ON o.id = q.converted_order_id
             SET q.converted_order_number = o.number
             WHERE q.converted_order_id IS NOT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes DROP converted_order_number');
    }
}
