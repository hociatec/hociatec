<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Entity;

use App\Module\TradeIn\Enum\TradeInStatus;
use App\Module\TradeIn\Repository\TradeInRequestRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TradeInRequestRepository::class)]
#[ORM\Table(name: 'trade_in_requests')]
#[ORM\HasLifecycleCallbacks]
class TradeInRequest
{
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

    public function getId(): ?int { return $this->id; }
    public function getReference(): string { return $this->reference; }
    public function getUser(): ?User { return $this->user; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getCategory(): string { return $this->category; }
    public function getProductName(): string { return $this->productName; }
    public function getBrand(): ?string { return $this->brand; }
    public function getModel(): ?string { return $this->model; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getConditionGrade(): string { return $this->conditionGrade; }
    public function isFunctional(): bool { return $this->functional; }
    public function hasAccessories(): bool { return $this->hasAccessories; }
    public function hasProofOfPurchase(): bool { return $this->hasProofOfPurchase; }
    public function getDescription(): string { return $this->description; }
    public function getCatalogProductId(): ?int { return $this->catalogProductId; }
    public function getCatalogProductName(): ?string { return $this->catalogProductName; }
    public function getEstimatedMinCents(): int { return $this->estimatedMinCents; }
    public function getEstimatedMaxCents(): int { return $this->estimatedMaxCents; }
    public function getOfferCents(): ?int { return $this->offerCents; }
    public function getAdminNote(): ?string { return $this->adminNote; }
    public function getStatus(): TradeInStatus { return $this->status; }
    public function getConsentAt(): \DateTimeImmutable { return $this->consentAt; }
    public function getOfferExpiresAt(): ?\DateTimeImmutable { return $this->offerExpiresAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function setStatus(TradeInStatus $status): self { $this->status = $status; $this->touch(); return $this; }
    public function setOffer(?int $offerCents, ?\DateTimeImmutable $expiresAt = null): self { $this->offerCents = null === $offerCents ? null : max(0, $offerCents); $this->offerExpiresAt = $expiresAt; $this->touch(); return $this; }
    public function setAdminNote(?string $note): self { $this->adminNote = null !== $note ? trim($note) : null; $this->touch(); return $this; }

    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
