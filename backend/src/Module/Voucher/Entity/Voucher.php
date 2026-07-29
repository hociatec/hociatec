<?php

declare(strict_types=1);

namespace App\Module\Voucher\Entity;

use App\Module\Voucher\Repository\VoucherRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoucherRepository::class)]
#[ORM\Table(name: 'vouchers')]
#[ORM\HasLifecycleCallbacks]
class Voucher
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED_CENTS = 'fixed_cents';
    private const MAX_PERCENT_DISCOUNT = 100;

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
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $recipientUserId = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $recipientEmail = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $name, string $code, string $discountType, int $discountValue)
    {
        $this->setName($name);
        $this->setCode($code);
        $this->changeDiscount($discountType, $discountValue);
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Le nom du voucher est obligatoire.');
        }

        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $code = mb_strtoupper(trim($code));
        if ('' === $code) {
            throw new \InvalidArgumentException('Le code du voucher est obligatoire.');
        }

        $this->code = $code;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $description = null !== $description ? trim($description) : null;
        $this->description = '' === $description ? null : $description;

        return $this;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): self
    {
        self::assertValidDiscountType($discountType);
        $this->discountType = $discountType;

        return $this;
    }

    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(int $discountValue): self
    {
        self::assertValidDiscountValue($this->discountType, $discountValue);
        $this->discountValue = $discountValue;

        return $this;
    }

    public function changeDiscount(string $discountType, int $discountValue): self
    {
        self::assertValidDiscountType($discountType);
        self::assertValidDiscountValue($discountType, $discountValue);
        $this->discountType = $discountType;
        $this->discountValue = $discountValue;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): self
    {
        self::assertValidDateRange($startsAt, $this->endsAt);
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        self::assertValidDateRange($this->startsAt, $endsAt);
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getRecipientUserId(): ?int
    {
        return $this->recipientUserId;
    }

    public function setRecipientUserId(?int $recipientUserId): self
    {
        $this->recipientUserId = $recipientUserId;

        return $this;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(?string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private static function assertValidDiscountType(string $discountType): void
    {
        if (!\in_array($discountType, [self::TYPE_PERCENT, self::TYPE_FIXED_CENTS], true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }
    }

    private static function assertValidDiscountValue(string $discountType, int $discountValue): void
    {
        if (0 === $discountValue) {
            return;
        }

        if ($discountValue <= 0) {
            throw new \InvalidArgumentException('La valeur de remise doit être supérieure à zéro.');
        }

        if (self::TYPE_PERCENT === $discountType && $discountValue > self::MAX_PERCENT_DISCOUNT) {
            throw new \InvalidArgumentException('La remise en pourcentage ne peut pas dépasser 100 %.');
        }
    }

    private static function assertValidDateRange(?\DateTimeImmutable $startsAt, ?\DateTimeImmutable $endsAt): void
    {
        if (null !== $startsAt && null !== $endsAt && $startsAt >= $endsAt) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }
    }
}
