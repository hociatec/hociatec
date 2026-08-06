<?php

declare(strict_types=1);

namespace App\Module\User\Application\Port;

use App\Module\User\Domain\Entity\User;

interface UserPasswordHasher
{
    public function hashPassword(User $user, string $plainPassword): string;

    public function isPasswordValid(User $user, string $plainPassword): bool;
}
