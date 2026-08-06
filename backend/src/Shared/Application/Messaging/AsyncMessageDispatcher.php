<?php

declare(strict_types=1);

namespace App\Shared\Application\Messaging;

interface AsyncMessageDispatcher
{
    public function dispatch(object $message): void;
}
