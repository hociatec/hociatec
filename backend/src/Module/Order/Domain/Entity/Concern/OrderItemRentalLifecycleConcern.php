<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity\Concern;

use App\Module\Order\Domain\Support\RentalPeriodCalculator;

trait OrderItemRentalLifecycleConcern
{
    public function getSellingType(): string
    {
        return $this->sellingType;
    }

    public function setSellingType(string $sellingType): self
    {
        $normalized = strtolower(trim($sellingType));
        $this->sellingType = 'rental' === $normalized ? 'rental' : 'sale';

        if ('sale' === $this->sellingType) {
            $this->rentalMonths = null;
            $this->rentalStartDate = null;
            $this->rentalEndDate = null;
            $this->clearRentalRequest();
        }

        return $this;
    }

    public function getRentalMonths(): ?int
    {
        return $this->rentalMonths;
    }

    public function setRentalMonths(?int $rentalMonths): self
    {
        $this->rentalMonths = null !== $rentalMonths ? max(1, $rentalMonths) : null;
        $this->refreshRentalEndDate();

        return $this;
    }

    public function getRentalStartDate(): ?\DateTimeImmutable
    {
        return $this->rentalStartDate;
    }

    public function getRentalStartDateString(): ?string
    {
        return RentalPeriodCalculator::formatDate($this->rentalStartDate);
    }

    public function setRentalStartDate(?\DateTimeImmutable $rentalStartDate): self
    {
        $this->rentalStartDate = RentalPeriodCalculator::normalizeDate($rentalStartDate);
        $this->refreshRentalEndDate();

        return $this;
    }

    public function getRentalEndDate(): ?\DateTimeImmutable
    {
        return $this->rentalEndDate;
    }

    public function getRentalEndDateString(): ?string
    {
        return RentalPeriodCalculator::formatDate($this->rentalEndDate);
    }

    public function setRentalEndDate(?\DateTimeImmutable $rentalEndDate): self
    {
        $this->rentalEndDate = RentalPeriodCalculator::normalizeDate($rentalEndDate);

        return $this;
    }

    public function getRentalRequestStatus(): string
    {
        return $this->rentalRequestStatus;
    }

    public function getRentalRequestType(): ?string
    {
        return $this->rentalRequestType;
    }

    public function getRentalRequestedEndDate(): ?\DateTimeImmutable
    {
        return $this->rentalRequestedEndDate;
    }

    public function getRentalRequestedEndDateString(): ?string
    {
        return RentalPeriodCalculator::formatDate($this->rentalRequestedEndDate);
    }

    public function getRentalRequestCreatedAt(): ?\DateTimeImmutable
    {
        return $this->rentalRequestCreatedAt;
    }

    public function getRentalOriginOrderItemId(): ?int
    {
        return $this->rentalOriginOrderItemId;
    }

    public function setRentalOriginOrderItemId(?int $rentalOriginOrderItemId): self
    {
        $this->rentalOriginOrderItemId = null !== $rentalOriginOrderItemId && $rentalOriginOrderItemId > 0
            ? $rentalOriginOrderItemId
            : null;

        return $this;
    }

    public function getRentalExtensionOrderId(): ?int
    {
        return $this->rentalExtensionOrderId;
    }

    public function getRentalReturnStatus(): string
    {
        return $this->rentalReturnStatus;
    }

    public function getRentalReturnMode(): ?string
    {
        return $this->rentalReturnMode;
    }

    public function getRentalReturnRequestedDate(): ?\DateTimeImmutable
    {
        return $this->rentalReturnRequestedDate;
    }

    public function getRentalReturnRequestedDateString(): ?string
    {
        return RentalPeriodCalculator::formatDate($this->rentalReturnRequestedDate);
    }

    public function getRentalReturnRequestedAt(): ?\DateTimeImmutable
    {
        return $this->rentalReturnRequestedAt;
    }

    public function getRentalReturnCompletedAt(): ?\DateTimeImmutable
    {
        return $this->rentalReturnCompletedAt;
    }

    public function requestRentalChange(string $type, \DateTimeImmutable $requestedEndDate): self
    {
        $normalizedType = strtolower(trim($type));
        if (!in_array($normalizedType, ['extend', 'end_early'], true)) {
            throw new \InvalidArgumentException('Type de demande de location invalide.');
        }

        $this->rentalRequestType = $normalizedType;
        $this->rentalRequestStatus = 'pending';
        $this->rentalRequestedEndDate = RentalPeriodCalculator::normalizeDate($requestedEndDate);
        $now = new \DateTimeImmutable();
        $this->rentalRequestCreatedAt ??= $now;
        $this->rentalRequestUpdatedAt = $now;

        return $this;
    }

    public function requestRentalExtensionPayment(\DateTimeImmutable $requestedEndDate, int $extensionOrderId): self
    {
        if ($extensionOrderId < 1) {
            throw new \InvalidArgumentException('Commande de prolongation invalide.');
        }

        $this->rentalRequestType = 'extend';
        $this->rentalRequestStatus = 'pending_payment';
        $this->rentalRequestedEndDate = RentalPeriodCalculator::normalizeDate($requestedEndDate);
        $this->rentalExtensionOrderId = $extensionOrderId;
        $now = new \DateTimeImmutable();
        $this->rentalRequestCreatedAt ??= $now;
        $this->rentalRequestUpdatedAt = $now;

        return $this;
    }

    public function clearRentalRequest(): self
    {
        $this->rentalRequestStatus = 'none';
        $this->rentalRequestType = null;
        $this->rentalRequestedEndDate = null;
        $this->rentalRequestCreatedAt = null;
        $this->rentalRequestUpdatedAt = null;
        $this->rentalExtensionOrderId = null;

        return $this;
    }

    public function applyApprovedRentalExtension(\DateTimeImmutable $requestedEndDate, int $rentalMonths): self
    {
        if ($rentalMonths < 1) {
            throw new \InvalidArgumentException('La durée de location doit être supérieure ou égale à 1 mois.');
        }

        $this->rentalMonths = $rentalMonths;
        $this->rentalEndDate = RentalPeriodCalculator::normalizeDate($requestedEndDate);
        $this->clearRentalRequest();

        return $this;
    }

    public function applyApprovedRentalEarlyEnd(\DateTimeImmutable $requestedEndDate, ?int $rentalMonths = null): self
    {
        if (null !== $rentalMonths && $rentalMonths < 1) {
            throw new \InvalidArgumentException('La durée de location doit être supérieure ou égale à 1 mois.');
        }

        if (null !== $rentalMonths) {
            $this->rentalMonths = $rentalMonths;
        }

        $this->rentalEndDate = RentalPeriodCalculator::normalizeDate($requestedEndDate);
        $this->clearRentalRequest();

        return $this;
    }

    public function scheduleRentalReturn(string $mode, \DateTimeImmutable $requestedDate): self
    {
        $normalizedMode = strtolower(trim($mode));
        if (!in_array($normalizedMode, ['pickup_home', 'dropoff_store'], true)) {
            throw new \InvalidArgumentException('Mode de restitution invalide.');
        }

        $this->rentalReturnMode = $normalizedMode;
        $this->rentalReturnStatus = 'scheduled';
        $this->rentalReturnRequestedDate = RentalPeriodCalculator::normalizeDate($requestedDate);
        $this->rentalReturnRequestedAt = new \DateTimeImmutable();
        $this->rentalReturnCompletedAt = null;

        return $this;
    }

    public function markRentalReturned(?\DateTimeImmutable $completedAt = null): self
    {
        $this->rentalReturnStatus = 'completed';
        $this->rentalReturnCompletedAt = $completedAt ?? new \DateTimeImmutable();

        return $this;
    }

    private function refreshRentalEndDate(): void
    {
        $this->rentalEndDate = RentalPeriodCalculator::calculateEndDate($this->rentalStartDate, $this->rentalMonths);
    }
}
