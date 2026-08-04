<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Projection;

final readonly class AccountNotificationFormatter
{
    /**
     * @return array{key: string, label: string, message: string, to: string, type: string, createdAt: string}
     */
    public function computedNotification(string $key, string $label, string $message, string $to, string $type, \DateTimeImmutable $createdAt): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'message' => $message,
            'to' => $this->safeInternalTarget($to),
            'type' => $type,
            'createdAt' => $createdAt->format(DATE_ATOM),
        ];
    }

    public function safeInternalTarget(string $target): string
    {
        $target = trim($target);
        if (!str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return '/mon-espace';
        }

        return $target;
    }

    public function formatFrenchDateTime(\DateTimeImmutable $date): string
    {
        return $date->format('d/m/Y H:i');
    }
}
