<?php

declare(strict_types=1);

namespace App\Module\Contact\Application\DTO;

use App\Shared\Domain\Normalization\EmailNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $subject,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $message,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            trim(self::stringValue($payload, 'name')),
            EmailNormalizer::normalize(self::stringValue($payload, 'email')),
            trim(self::stringValue($payload, 'subject')),
            trim(self::stringValue($payload, 'message')),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function stringValue(array $payload, string $key): string
    {
        $value = $payload[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
