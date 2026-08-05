<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Port;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;

interface AppointmentRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Appointment;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<Appointment>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<Appointment> */
    public function findBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array;

    /** @return list<Appointment> */
    public function findForUser(User $user, ?string $status = null): array;
}
