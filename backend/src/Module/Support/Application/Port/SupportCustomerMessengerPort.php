<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Port;

use App\Module\User\Domain\Entity\User;

interface SupportCustomerMessengerPort
{
    public function send(User $user, string $subject, string $message): void;
}
