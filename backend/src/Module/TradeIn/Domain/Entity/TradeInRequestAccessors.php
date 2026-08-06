<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInClosure;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInPrivateDocument;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductCondition;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductIdentity;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\TradeIn\Domain\ValueObject\TradeInPurchase;
use App\Module\User\Domain\Entity\User;

trait TradeInRequestAccessors
{
    public static function fromSubmittedData(
        string $reference,
        ?User $user,
        TradeInApplicant $applicant,
        TradeInProductSnapshot $product,
        TradeInEstimate $estimate,
        \DateTimeImmutable $consentAt,
    ): self {
        return new self($reference, $user, $applicant, $product, $estimate, $consentAt);
    }

    public static function fromLegacySubmittedScalars(
        string $reference,
        ?User $user,
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $category,
        string $productName,
        int $purchasePriceCents,
        int $purchaseYear,
        ?string $brand,
        ?string $model,
        ?string $serialNumber,
        string $conditionGrade,
        bool $functional,
        bool $hasAccessories,
        bool $hasProofOfPurchase,
        string $description,
        ?int $catalogProductId,
        ?string $catalogProductName,
        int $estimatedMinCents,
        int $estimatedMaxCents,
        \DateTimeImmutable $consentAt,
    ): self {
        return new self(
            $reference,
            $user,
            new TradeInApplicant($firstName, $lastName, $email, $phone),
            new TradeInProductSnapshot(
                new TradeInProductIdentity($category, $productName, $brand, $model, $serialNumber, $catalogProductId, $catalogProductName),
                new TradeInPurchase($purchasePriceCents, $purchaseYear),
                new TradeInProductCondition($conditionGrade, $functional, $hasAccessories, $hasProofOfPurchase, $description),
            ),
            new TradeInEstimate($estimatedMinCents, $estimatedMaxCents, null, null),
            $consentAt,
        );
    }

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
            new TradeInProductIdentity(
                $this->category,
                $this->productName,
                $this->brand,
                $this->model,
                $this->serialNumber,
                $this->catalogProductId,
                $this->catalogProductName,
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setStatus(TradeInStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function setOffer(?int $offerCents, ?\DateTimeImmutable $expiresAt = null): self
    {
        if (null !== $offerCents && $offerCents < 0) {
            throw new \InvalidArgumentException('Le montant de l’offre ne peut pas être négatif.');
        }

        $this->offerCents = $offerCents;
        $this->offerExpiresAt = $expiresAt;
        $this->touch();

        return $this;
    }

    public function setClosure(int $finalOfferCents, string $paymentMethod, string $paymentStatus, ?string $transactionReference, ?\DateTimeImmutable $paidAt): self
    {
        $closure = TradeInClosure::fromInput($finalOfferCents, $paymentMethod, $paymentStatus, $transactionReference, $paidAt);
        $this->finalOfferCents = $closure->finalOfferCents;
        $this->paymentMethod = $closure->paymentMethod;
        $this->paymentStatus = $closure->paymentStatus;
        $this->transactionReference = $closure->transactionReference;
        $this->paidAt = $closure->paidAt;
        $this->closedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function setRib(string $path, string $originalName, int $size, string $sha256): self
    {
        $this->ribPath = $path;
        $this->ribOriginalName = $originalName;
        $this->ribSize = $size;
        $this->ribSha256 = $sha256;
        $this->touch();

        return $this;
    }

    public function setReceiptPath(?string $path): self
    {
        $this->receiptPath = $path;
        $this->touch();

        return $this;
    }

    public function setVoucherCode(?string $code): self
    {
        $this->voucherCode = null !== $code && '' !== trim($code) ? trim($code) : null;
        $this->touch();

        return $this;
    }

    public function setAdminNote(?string $note): self
    {
        $this->adminNote = null !== $note ? trim($note) : null;
        $this->touch();

        return $this;
    }
}
