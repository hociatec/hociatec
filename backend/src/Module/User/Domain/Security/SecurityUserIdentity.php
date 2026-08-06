<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Security;

interface SecurityUserIdentity
{
    public function getUserIdentifier(): string;

    /** @return list<string> */
    public function getRoles(): array;

    public function getPassword(): string;
}
