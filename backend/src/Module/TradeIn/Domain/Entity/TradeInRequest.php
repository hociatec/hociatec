<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\ValueObject\TradeInClosure;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trade_in_requests')]
#[ORM\HasLifecycleCallbacks]
class TradeInRequest
{
    use TradeInRequestViewTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(length: 80)]
    private string $firstName;

    #[ORM\Column(length: 80)]
    private string $lastName;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 30)]
    private string $phone;

    #[ORM\Column(length: 80)]
    private string $category;

    #[ORM\Column(length: 180)]
    private string $productName;

    #[ORM\Column(type: 'integer')]
    private int $purchasePriceCents;

    #[ORM\Column(type: 'smallint')]
    private int $purchaseYear;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $brand;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $model;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $serialNumber;

    #[ORM\Column(length: 30)]
    private string $conditionGrade;

    #[ORM\Column(type: 'boolean')]
    private bool $functional;

    #[ORM\Column(type: 'boolean')]
    private bool $hasAccessories;

    #[ORM\Column(type: 'boolean')]
    private bool $hasProofOfPurchase;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(nullable: true)]
    private ?int $catalogProductId;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $catalogProductName;

    #[ORM\Column(type: 'integer')]
    private int $estimatedMinCents;

    #[ORM\Column(type: 'integer')]
    private int $estimatedMaxCents;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $offerCents = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $finalOfferCents = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 30)]
    private string $paymentStatus = 'pending';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $transactionReference = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ribPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ribOriginalName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ribSize = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ribSha256 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $receiptPath = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $voucherCode = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column(enumType: TradeInStatus::class)]
    private TradeInStatus $status = TradeInStatus::SUBMITTED;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $consentAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $offerExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
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
    ) {
        $this->reference = $reference;
        $this->user = $user;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->category = $category;
        $this->productName = $productName;
        $this->purchasePriceCents = max(0, $purchasePriceCents);
        $this->purchaseYear = $purchaseYear;
        $this->brand = $brand;
        $this->model = $model;
        $this->serialNumber = $serialNumber;
        $this->conditionGrade = $conditionGrade;
        $this->functional = $functional;
        $this->hasAccessories = $hasAccessories;
        $this->hasProofOfPurchase = $hasProofOfPurchase;
        $this->description = $description;
        $this->catalogProductId = $catalogProductId;
        $this->catalogProductName = $catalogProductName;
        $this->estimatedMinCents = max(0, $estimatedMinCents);
        $this->estimatedMaxCents = max($this->estimatedMinCents, $estimatedMaxCents);
        $this->consentAt = $consentAt;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function setStatus(TradeInStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function setOffer(?int $offerCents, ?\DateTimeImmutable $expiresAt = null): self
    {
        $this->offerCents = null === $offerCents ? null : max(0, $offerCents);
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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
