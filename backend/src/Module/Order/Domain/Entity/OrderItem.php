<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Support\RentalPeriodCalculator;
use App\Shared\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 180)]
    private string $productName;

    #[ORM\Column(length: 60)]
    private string $productSku;

    #[ORM\Column(type: 'integer')]
    private int $unitPriceCents;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer', options: ['default' => 2000])]
    private int $vatRateBps = 2000;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lineSubtotalCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lineVatCents = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $lineTotalCents = 0;

    #[ORM\Column(length: 10, options: ['default' => 'sale'])]
    private string $sellingType = 'sale';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalMonths = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalStartDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalEndDate = null;

    #[ORM\Column(length: 20, options: ['default' => 'none'])]
    private string $rentalRequestStatus = 'none';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $rentalRequestType = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalRequestedEndDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalRequestCreatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalRequestUpdatedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalOriginOrderItemId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rentalExtensionOrderId = null;

    #[ORM\Column(length: 30, options: ['default' => 'none'])]
    private string $rentalReturnStatus = 'none';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $rentalReturnMode = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalReturnRequestedDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalReturnRequestedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rentalReturnCompletedAt = null;

    public function __construct(string $productName, string $productSku, int $unitPriceCents, int $quantity)
    {
        $this->productName = $productName;
        $this->productSku = $productSku;
        $this->unitPriceCents = Money::fromCents($unitPriceCents)->cents();
        if ($quantity < 1) {
            throw new \InvalidArgumentException('La quantité doit être supérieure ou égale à 1.');
        }
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getVatRateBps(): int
    {
        return $this->vatRateBps;
    }

    public function setVatRateBps(int $vatRateBps): self
    {
        if ($vatRateBps < 0) {
            throw new \InvalidArgumentException('Le taux de TVA ne peut pas être négatif.');
        }

        $this->vatRateBps = $vatRateBps;

        return $this;
    }

    public function getLineSubtotalCents(): int
    {
        return $this->lineSubtotalCents;
    }

    public function setLineSubtotalCents(int $lineSubtotalCents): self
    {
        $this->lineSubtotalCents = Money::fromCents($lineSubtotalCents)->cents();

        return $this;
    }

    public function getLineVatCents(): int
    {
        return $this->lineVatCents;
    }

    public function setLineVatCents(int $lineVatCents): self
    {
        $this->lineVatCents = Money::fromCents($lineVatCents)->cents();

        return $this;
    }

    public function getLineTotalCents(): int
    {
        return $this->lineTotalCents;
    }

    public function setLineTotalCents(int $lineTotalCents): self
    {
        $this->lineTotalCents = Money::fromCents($lineTotalCents)->cents();

        return $this;
    }

    public function replaceLineTotals(int $lineSubtotalCents, int $lineVatCents, int $lineTotalCents): self
    {
        return $this
            ->setLineSubtotalCents($lineSubtotalCents)
            ->setLineVatCents($lineVatCents)
            ->setLineTotalCents($lineTotalCents);
    }

    public function getLinePriceCents(): int
    {
        return $this->lineTotalCents > 0 ? $this->lineTotalCents : $this->unitPriceCents * $this->quantity;
    }

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
