<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CustomerEmailInput
{
    public function __construct(#[Assert\NotBlank] #[Assert\Length(max: 255)] public string $subject, #[Assert\NotBlank] public string $message)
    {
    }

    /** @param array<string,mixed> $p */
    public static function fromArray(array $p): self
    {
        return new self(is_string($p['subject'] ?? null) ? trim($p['subject']) : '', is_string($p['message'] ?? null) ? trim($p['message']) : '');
    }
}
