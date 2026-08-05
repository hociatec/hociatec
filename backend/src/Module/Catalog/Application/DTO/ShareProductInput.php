<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Shared\Domain\Normalization\EmailNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ShareProductInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,
    ) {
    }

    public static function fromPayload(mixed $payload): self
    {
        $email = is_array($payload) && is_string($payload['email'] ?? null)
            ? $payload['email']
            : '';

        return new self(EmailNormalizer::normalize($email));
    }
}
