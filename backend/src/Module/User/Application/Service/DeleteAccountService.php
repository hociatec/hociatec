<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;

final readonly class DeleteAccountService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function delete(User $user): void
    {
        $this->users->remove($user, true);
    }
}
