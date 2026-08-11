<?php

declare(strict_types=1);

namespace App\Shared\Application\Security;

use App\Module\User\Domain\Entity\User;

interface AuthenticatedUserProvider
{
    public function currentUser(): ?User;
}
