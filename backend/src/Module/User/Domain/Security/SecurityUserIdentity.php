<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface SecurityUserIdentity extends UserInterface, PasswordAuthenticatedUserInterface
{
}
