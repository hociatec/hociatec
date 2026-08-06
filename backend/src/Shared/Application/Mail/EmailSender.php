<?php

declare(strict_types=1);

namespace App\Shared\Application\Mail;

use Symfony\Component\Mime\Email;

interface EmailSender
{
    public function send(Email $email): void;
}
