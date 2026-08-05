<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\DTO;

use App\Shared\Domain\Normalization\EmailNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RequestPasswordResetInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $email = $payload['email'] ?? '';

        return new self(EmailNormalizer::normalize(is_string($email) ? $email : ''));
    }
}
