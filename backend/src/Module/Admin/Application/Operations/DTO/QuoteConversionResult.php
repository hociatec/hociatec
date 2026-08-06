<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\DTO;

final readonly class QuoteConversionResult
{
    /**
     * @param array<string, mixed> $order
     */
    public function __construct(
        public array $order,
        public bool $emailNotificationSent,
        public ?string $emailNotificationError,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'order' => $this->order,
            'emailNotificationSent' => $this->emailNotificationSent,
            'emailNotificationError' => $this->emailNotificationError,
        ];
    }
}
