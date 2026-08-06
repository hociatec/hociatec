<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInCatalogReference;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\TradeIn\Domain\ValueObject\TradeInTechnicalIdentity;
use App\Module\User\Domain\Entity\User;

trait TradeInRequestProductAccessorsTrait
{
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->userId ?? $this->extractUserId($this->user);
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function applicant(): TradeInApplicant
    {
        return new TradeInApplicant($this->firstName, $this->lastName, $this->email, $this->phone);
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getPurchasePriceCents(): int
    {
        return $this->purchasePriceCents;
    }

    public function getPurchaseYear(): int
    {
        return $this->purchaseYear;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function getConditionGrade(): string
    {
        return $this->conditionGrade;
    }

    public function isFunctional(): bool
    {
        return $this->functional;
    }

    public function hasAccessories(): bool
    {
        return $this->hasAccessories;
    }

    public function hasProofOfPurchase(): bool
    {
        return $this->hasProofOfPurchase;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCatalogProductId(): ?int
    {
        return $this->catalogProductId;
    }

    public function getCatalogProductName(): ?string
    {
        return $this->catalogProductName;
    }

    public function productSnapshot(): TradeInProductSnapshot
    {
        return new TradeInProductSnapshot(
            new TradeInProductIdentity($this->category, $this->productName, new TradeInTechnicalIdentity($this->brand, $this->model, $this->serialNumber), new TradeInCatalogReference($this->catalogProductId, $this->catalogProductName)),
            new TradeInPurchase($this->purchasePriceCents, $this->purchaseYear),
            new TradeInProductCondition($this->conditionGrade, $this->functional, $this->hasAccessories, $this->hasProofOfPurchase, $this->description),
        );
    }

    public function getEstimatedMinCents(): int
    {
        return $this->estimatedMinCents;
    }

    public function getEstimatedMaxCents(): int
    {
        return $this->estimatedMaxCents;
    }

    public function getOfferCents(): ?int
    {
        return $this->offerCents;
    }

    public function getFinalOfferCents(): ?int
    {
        return $this->finalOfferCents;
    }

    public function getOfferExpiresAt(): ?\DateTimeImmutable
    {
        return $this->offerExpiresAt;
    }

    public function estimate(): TradeInEstimate
    {
        return new TradeInEstimate($this->estimatedMinCents, $this->estimatedMaxCents, $this->offerCents, $this->offerExpiresAt);
    }
}
