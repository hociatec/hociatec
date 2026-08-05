<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Port;

use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;

interface CartSessionRepositoryPort
{
    public function findOneByToken(string $token): ?CartSession;

    public function findForUpdate(int $id): ?CartSession;

    public function findOneByUser(User $user): ?CartSession;

    public function findOneByUserId(int $userId): ?CartSession;

    public function clearUnitOfWork(): void;
}
