<?php

declare(strict_types=1);

namespace App\Module\Marketing\Domain\Entity;

final readonly class EmailCampaignContentSnapshot
{
    public function __construct(
        public string $subject,
        public string $html,
        public ?string $text = null,
    ) {
    }
}
