<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Message;

final readonly class UserCommunicationEmailMessage
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $message,
        public string $targetUrl,
        public string $type,
    ) {
    }
}
