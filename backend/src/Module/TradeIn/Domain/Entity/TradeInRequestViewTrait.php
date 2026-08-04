<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInClosure;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInPrivateDocument;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\User\Domain\Entity\User;

trait TradeInRequestViewTrait
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
            $this->category,
            $this->productName,
            $this->purchasePriceCents,
            $this->purchaseYear,
            $this->brand,
            $this->model,
            $this->serialNumber,
            $this->conditionGrade,
            $this->functional,
            $this->hasAccessories,
            $this->hasProofOfPurchase,
            $this->description,
            $this->catalogProductId,
            $this->catalogProductName,
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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getRibPath(): ?string
    {
        return $this->ribPath;
    }

    public function getRibOriginalName(): ?string
    {
        return $this->ribOriginalName;
    }

    public function getRibSize(): ?int
    {
        return $this->ribSize;
    }

    public function getReceiptPath(): ?string
    {
        return $this->receiptPath;
    }

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function getStatus(): TradeInStatus
    {
        return $this->status;
    }

    public function getConsentAt(): \DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function getOfferExpiresAt(): ?\DateTimeImmutable
    {
        return $this->offerExpiresAt;
    }

    public function estimate(): TradeInEstimate
    {
        return new TradeInEstimate($this->estimatedMinCents, $this->estimatedMaxCents, $this->offerCents, $this->offerExpiresAt);
    }

    public function closure(): ?TradeInClosure
    {
        if (null === $this->finalOfferCents || null === $this->paymentMethod) {
            return null;
        }

        return new TradeInClosure($this->finalOfferCents, $this->paymentMethod, $this->paymentStatus, $this->transactionReference, $this->paidAt);
    }

    public function ribDocument(): ?TradeInPrivateDocument
    {
        if (null === $this->ribPath) {
            return null;
        }

        return new TradeInPrivateDocument($this->ribPath, $this->ribOriginalName, $this->ribSize, $this->ribSha256);
    }

    public function receiptDocument(): ?TradeInPrivateDocument
    {
        if (null === $this->receiptPath) {
            return null;
        }

        return new TradeInPrivateDocument($this->receiptPath);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
