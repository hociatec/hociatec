<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Port;

use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\LockMode;

interface BetaTesterProfileRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaTesterProfile;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<BetaTesterProfile>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    public function findOneByUser(User $user): ?BetaTesterProfile;
}
