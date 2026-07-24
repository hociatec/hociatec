<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724023000 extends AbstractMigration
{
    private const CANONICAL_INDEX_NAME = 'UNIQ_USERS_EMAIL';

    public function getDescription(): string
    {
        return 'Give the unique users email index a stable application-level name';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $emailIndex = $this->findUniqueEmailIndex();
        if ($emailIndex !== null && strcasecmp($emailIndex->getName(), self::CANONICAL_INDEX_NAME) === 0) {
            return;
        }

        if ($emailIndex !== null) {
            $this->addSql(sprintf(
                'DROP INDEX %s ON users',
                $this->connection->quoteIdentifier($emailIndex->getName()),
            ));
        }

        $this->addSql('CREATE UNIQUE INDEX UNIQ_USERS_EMAIL ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $emailIndex = $this->findUniqueEmailIndex();
        if ($emailIndex === null || strcasecmp($emailIndex->getName(), self::CANONICAL_INDEX_NAME) !== 0) {
            return;
        }

        $this->addSql('DROP INDEX UNIQ_USERS_EMAIL ON users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    private function findUniqueEmailIndex(): ?Index
    {
        foreach ($this->connection->createSchemaManager()->listTableIndexes('users') as $index) {
            if ($index->isUnique() && array_map('strtolower', $index->getColumns()) === ['email']) {
                return $index;
            }
        }

        return null;
    }
}
