<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Port;

use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\LockMode;

interface TrainingEnrollmentRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TrainingEnrollment;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<TrainingEnrollment>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    public function countActiveForSession(TrainingSession $session): int;

    public function countActiveForSessionSlot(TrainingSession $session, \DateTimeImmutable $startsAt, \DateTimeImmutable $endsAt): int;

    public function findOneForUserAndSession(User $user, TrainingSession $session): ?TrainingEnrollment;

    public function findOneByStripeSessionId(string $stripeSessionId): ?TrainingEnrollment;

    /** @return list<TrainingEnrollment> */
    public function findForUser(User $user): array;
}
