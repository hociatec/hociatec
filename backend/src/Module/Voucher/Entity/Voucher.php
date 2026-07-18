<?php

declare(strict_types=1);

namespace App\Module\Voucher\Entity;

use App\Module\Voucher\Repository\VoucherRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoucherRepository::class)]
#[ORM\Table(name: 'vouchers')]
#[ORM\HasLifecycleCallbacks]
class Voucher
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED_CENTS = 'fixed_cents';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 140)]
    private string $name;

    #[ORM\Column(length: 64, unique: true)]
    private string $code;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $discountType = self::TYPE_FIXED_CENTS;

    #[ORM\Column(type: 'integer')]
    private int $discountValue = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $recipientUserId = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $recipientEmail = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $name, string $code, string $discountType, int $discountValue)
    {
        $this->name = $name;
        $this->code = $code;
        $this->discountType = $discountType;
        $this->discountValue = max(0, $discountValue);
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = mb_strtoupper(trim($code)); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getDiscountType(): string { return $this->discountType; }
    public function setDiscountType(string $discountType): self { $this->discountType = $discountType; return $this; }
    public function getDiscountValue(): int { return $this->discountValue; }
    public function setDiscountValue(int $discountValue): self { $this->discountValue = max(0, $discountValue); return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function getStartsAt(): ?DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(?DateTimeImmutable $startsAt): self { $this->startsAt = $startsAt; return $this; }
    public function getEndsAt(): ?DateTimeImmutable { return $this->endsAt; }
    public function setEndsAt(?DateTimeImmutable $endsAt): self { $this->endsAt = $endsAt; return $this; }
    public function getRecipientUserId(): ?int { return $this->recipientUserId; }
    public function setRecipientUserId(?int $recipientUserId): self { $this->recipientUserId = $recipientUserId; return $this; }
    public function getRecipientEmail(): ?string { return $this->recipientEmail; }
    public function setRecipientEmail(?string $recipientEmail): self { $this->recipientEmail = $recipientEmail; return $this; }
    public function getSentAt(): ?DateTimeImmutable { return $this->sentAt; }
    public function setSentAt(?DateTimeImmutable $sentAt): self { $this->sentAt = $sentAt; return $this; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
