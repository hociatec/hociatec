<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Port;

use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Shared\Application\LockMode;

interface EmailTemplateRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?EmailTemplate;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<EmailTemplate>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    public function findOneBySlug(string $slug): ?EmailTemplate;

    public function findActiveOneByScenarioKey(string $scenarioKey): ?EmailTemplate;
}
