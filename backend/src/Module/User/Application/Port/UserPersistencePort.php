<?php

declare(strict_types=1);

namespace App\Module\User\Application\Port;

use App\Module\User\Domain\Entity\User;

interface UserPersistencePort
{
    public function save(User $user): void;

    public function remove(User $user): void;

    public function flush(): void;
}
