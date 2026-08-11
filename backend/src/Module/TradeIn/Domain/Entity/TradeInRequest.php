<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\ValueObject\TradeInApplicant;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\TradeIn\Domain\ValueObject\TradeInProductSnapshot;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'trade_in_requests')]
#[ORM\Index(name: 'idx_trade_in_status_created', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'idx_trade_in_requester_created', columns: ['requester_user_id', 'created_at'])]
#[ORM\Index(name: 'idx_trade_in_email', columns: ['email'])]
#[ORM\Index(name: 'idx_trade_in_closed_at', columns: ['closed_at'])]
#[ORM\HasLifecycleCallbacks]
/**
 * Settlement accessors compose TradeInClosure and TradeInPrivateDocument value objects.
 */
class TradeInRequest
{
    use TradeInRequestAccessors;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: 'App\Module\User\Domain\Entity\User')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(name: 'requester_user_id', type: 'integer', nullable: true)]
    private ?int $userId = null;

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
        TradeInApplicant $applicant,
        TradeInProductSnapshot $product,
        TradeInEstimate $estimate,
        \DateTimeImmutable $consentAt,
    ) {
        $this->reference = $reference;
        $this->user = $user;
        $this->userId = $this->extractUserId($user);
        $this->firstName = $applicant->firstName;
        $this->lastName = $applicant->lastName;
        $this->email = $applicant->email;
        $this->phone = $applicant->phone;
        $this->category = $product->category;
        $this->productName = $product->productName;
        $this->purchasePriceCents = $product->purchasePriceCents;
        $this->purchaseYear = $product->purchaseYear;
        $this->brand = $product->brand;
        $this->model = $product->model;
        $this->serialNumber = $product->serialNumber;
        $this->conditionGrade = $product->conditionGrade;
        $this->functional = $product->functional;
        $this->hasAccessories = $product->hasAccessories;
        $this->hasProofOfPurchase = $product->hasProofOfPurchase;
        $this->description = $product->description;
        $this->catalogProductId = $product->catalogProductId;
        $this->catalogProductName = $product->catalogProductName;
        $this->estimatedMinCents = $estimate->minCents;
        $this->estimatedMaxCents = $estimate->maxCents;
        $this->offerCents = $estimate->offerCents;
        $this->offerExpiresAt = $estimate->offerExpiresAt;
        $this->consentAt = $consentAt;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function anonymizePersonalData(): self
    {
        $this->firstName = 'Deleted';
        $this->lastName = 'User';
        $this->email = 'deleted@privacy.invalid';
        $this->phone = '0000000000';
        $this->serialNumber = null;
        $this->description = '[deleted]';

        return $this;
    }

    private function extractUserId(?User $user): ?int
    {
        return $user?->getId();
    }
}
