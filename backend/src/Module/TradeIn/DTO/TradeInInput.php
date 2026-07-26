<?php

declare(strict_types=1);

namespace App\Module\TradeIn\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TradeInInput
{
    public function __construct(
        #[Assert\NotBlank, Assert\Length(max: 80)] public string $firstName,
        #[Assert\NotBlank, Assert\Length(max: 80)] public string $lastName,
        #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)] public string $email,
        #[Assert\NotBlank, Assert\Length(max: 30)] public string $phone,
        #[Assert\Choice(choices: ['smartphone', 'ordinateur', 'tablette', 'console', 'appareil-photo', 'audio', 'electromenager', 'autre'])] public string $category,
        #[Assert\NotBlank, Assert\Length(max: 180)] public string $productName,
        #[Assert\Length(max: 120)] public ?string $brand,
        #[Assert\Length(max: 120)] public ?string $model,
        #[Assert\Length(max: 120)] public ?string $serialNumber,
        #[Assert\Choice(choices: ['comme_neuf', 'tres_bon', 'bon', 'correct', 'hors_service'])] public string $conditionGrade,
        public bool $functional,
        public bool $hasAccessories,
        public bool $hasProofOfPurchase,
        #[Assert\NotBlank, Assert\Length(max: 5000)] public string $description,
        public ?int $catalogProductId,
        #[Assert\IsTrue] public bool $consent,
    ) {}

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $string = static fn (string $key): string => is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
        $nullable = static fn (string $key): ?string => '' === $string($key) ? null : $string($key);
        $bool = static fn (string $key): bool => true === ($payload[$key] ?? false) || '1' === ($payload[$key] ?? null);

        return new self($string('firstName'), $string('lastName'), $string('email'), $string('phone'), $string('category'), $string('productName'), $nullable('brand'), $nullable('model'), $nullable('serialNumber'), $string('conditionGrade'), $bool('functional'), $bool('hasAccessories'), $bool('hasProofOfPurchase'), $string('description'), is_numeric($payload['catalogProductId'] ?? null) ? (int) $payload['catalogProductId'] : null, $bool('consent'));
    }
}
