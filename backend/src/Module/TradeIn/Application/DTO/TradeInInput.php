<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\DTO;

use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInCatalogReference;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\TradeIn\Domain\ValueObject\TradeInTechnicalIdentity;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TradeInInput
{
    #[Assert\NotBlank, Assert\Length(max: 80)]
    public string $firstName;
    #[Assert\NotBlank, Assert\Length(max: 80)]
    public string $lastName;
    #[Assert\NotBlank, Assert\Email, Assert\Length(max: 180)]
    public string $email;
    #[Assert\NotBlank, Assert\Length(max: 30)]
    public string $phone;
    #[Assert\Choice(choices: ['smartphone', 'ordinateur', 'tablette', 'console', 'appareil-photo', 'audio', 'electromenager', 'autre'])]
    public string $category;
    #[Assert\NotBlank, Assert\Length(max: 180)]
    public string $productName;
    #[Assert\Positive, Assert\LessThanOrEqual(value: 100000000)]
    public int $purchasePriceCents;
    #[Assert\Range(min: 1980, max: 2100)]
    public int $purchaseYear;
    #[Assert\Length(max: 120)]
    public ?string $brand;
    #[Assert\Length(max: 120)]
    public ?string $model;
    #[Assert\Length(max: 120)]
    public ?string $serialNumber;
    #[Assert\Choice(choices: ['comme_neuf', 'tres_bon', 'bon', 'correct', 'hors_service'])]
    public string $conditionGrade;
    public bool $functional;
    public bool $hasAccessories;
    public bool $hasProofOfPurchase;
    #[Assert\NotBlank, Assert\Length(max: 5000)]
    public string $description;
    public ?int $catalogProductId;
    #[Assert\IsTrue]
    public bool $consent;

    public function __construct(
        private TradeInContactInput $contact,
        private TradeInProductInput $product,
        bool $consent,
    ) {
        $this->firstName = $contact->firstName;
        $this->lastName = $contact->lastName;
        $this->email = $contact->email;
        $this->phone = $contact->phone;
        $this->category = $product->category;
        $this->productName = $product->productName;
        $this->purchasePriceCents = $product->purchasePriceCents;
        $this->purchaseYear = $product->purchaseYear;
        $this->brand = $product->technicalIdentity->brand;
        $this->model = $product->technicalIdentity->model;
        $this->serialNumber = $product->technicalIdentity->serialNumber;
        $this->conditionGrade = $product->condition->conditionGrade;
        $this->functional = $product->condition->functional;
        $this->hasAccessories = $product->condition->hasAccessories;
        $this->hasProofOfPurchase = $product->condition->hasProofOfPurchase;
        $this->description = $product->condition->description;
        $this->catalogProductId = $product->catalogProductId;
        $this->consent = $consent;
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $string = static fn (string $key): string => is_string($payload[$key] ?? null) ? trim($payload[$key]) : '';
        $nullable = static fn (string $key): ?string => '' === $string($key) ? null : $string($key);
        $bool = static fn (string $key): bool => in_array($payload[$key] ?? false, [true, '1', 'true', 'on'], true);

        return new self(
            new TradeInContactInput(
                $string('firstName'),
                $string('lastName'),
                $string('email'),
                $string('phone'),
            ),
            new TradeInProductInput(
                $string('category'),
                $string('productName'),
                is_numeric($payload['purchasePriceCents'] ?? null) ? (int) $payload['purchasePriceCents'] : 0,
                is_numeric($payload['purchaseYear'] ?? null) ? (int) $payload['purchaseYear'] : 0,
                new TradeInTechnicalIdentityInput(
                    $nullable('brand'),
                    $nullable('model'),
                    $nullable('serialNumber'),
                ),
                new TradeInConditionInput(
                    $string('conditionGrade'),
                    $bool('functional'),
                    $bool('hasAccessories'),
                    $bool('hasProofOfPurchase'),
                    $string('description'),
                ),
                is_numeric($payload['catalogProductId'] ?? null) ? (int) $payload['catalogProductId'] : null,
            ),
            $bool('consent'),
        );
    }

    public function withContact(string $firstName, string $lastName, string $email, string $phone): self
    {
        return new self(
            new TradeInContactInput($firstName, $lastName, $email, $phone),
            $this->product,
            $this->consent,
        );
    }

    public function applicant(): TradeInApplicant
    {
        return new TradeInApplicant($this->firstName, $this->lastName, $this->email, $this->phone);
    }

    public function productSnapshot(?int $catalogProductId = null, ?string $catalogProductName = null): TradeInProductSnapshot
    {
        return new TradeInProductSnapshot(
            new TradeInProductIdentity(
                $this->category,
                $this->productName,
                new TradeInTechnicalIdentity($this->brand, $this->model, $this->serialNumber),
                new TradeInCatalogReference($catalogProductId ?? $this->catalogProductId, $catalogProductName),
            ),
            new TradeInPurchase($this->purchasePriceCents, $this->purchaseYear),
            new TradeInProductCondition(
                $this->conditionGrade,
                $this->functional,
                $this->hasAccessories,
                $this->hasProofOfPurchase,
                $this->description,
            ),
        );
    }
}
