<?php

declare(strict_types=1);

namespace App\Module\User\Application\Port;

use App\Module\User\Domain\Entity\User;
use Doctrine\DBAL\LockMode;

interface UserRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    public function save(User $user): void;

    public function remove(User $user): void;

    public function findForUpdate(int $id): ?User;

    public function existsByEmail(string $email): bool;

    public function existsByEmailExcludingUser(string $email, int $userId): bool;

    public function findOneByEmailInsensitive(string $email): ?User;

    public function findOneByVerificationTokens(string $hashedToken, string $legacyToken): ?User;

    public function findOneByPasswordResetToken(string $token): ?User;

    /** @return list<User> */
    public function findAdmins(): array;

    /** @return list<User> */
    public function findNewsEmailSubscribers(): array;

    /** @return list<User> */
    public function findLoyaltyCustomers(string $search, int $limit, int $offset): array;

    public function countLoyaltyCustomers(string $search): int;

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>|null $orderBy
     *
     * @return list<User>
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /** @param array<string, mixed> $criteria */
    public function count(array $criteria): int;

    /** @return list<array<string, mixed>> */
    public function findAdminCustomerRows(?string $search = null, string $sort = 'recent_order', int $limit = 100, int $offset = 0): array;
}
