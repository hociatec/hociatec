<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Policy\VoucherEligibilityPolicy;
use App\Module\Voucher\Domain\ValueObject\VoucherDiscount;
use App\Module\Voucher\Domain\ValueObject\VoucherRecipientConstraint;
use App\Module\Voucher\Domain\ValueObject\VoucherValidityPeriod;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
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
        $code = self::normalizeCode($code);
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
        new VoucherDiscount($discountType, 0);
        $this->discountType = $discountType;

        return $this;
    }

    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(int $discountValue): self
    {
        new VoucherDiscount($this->discountType, $discountValue);
        $this->discountValue = $discountValue;

        return $this;
    }

    public function changeDiscount(string $discountType, int $discountValue): self
    {
        $discount = new VoucherDiscount($discountType, $discountValue);
        $discountType = $discount->type;
        $discountValue = $discount->value;
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
        new VoucherValidityPeriod($startsAt, $this->endsAt);
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        new VoucherValidityPeriod($this->startsAt, $endsAt);
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
        $recipientEmail = null !== $recipientEmail ? trim($recipientEmail) : null;
        $this->recipientEmail = '' === $recipientEmail ? null : $recipientEmail;

        return $this;
    }

    public static function normalizeCode(?string $code): string
    {
        return mb_strtoupper(trim((string) $code));
    }

    public function hasStartedAt(\DateTimeImmutable $now): bool
    {
        return $this->validityPeriod()->hasStartedAt($now);
    }

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return $this->validityPeriod()->isExpiredAt($now);
    }

    public function hasRecipientConstraint(): bool
    {
        return $this->recipientConstraint()->exists();
    }

    public function matchesRecipient(?User $user): bool
    {
        return $this->recipientConstraint()->matches($user);
    }

    public function canBeUsedBy(?User $user, \DateTimeImmutable $now): bool
    {
        return (new VoucherEligibilityPolicy())->canBeUsedBy($this, $user, $now);
    }

    public function canBeNotifiedTo(User $user, \DateTimeImmutable $now): bool
    {
        return $this->canBeUsedBy($user, $now);
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

    public function discount(): VoucherDiscount
    {
        return new VoucherDiscount($this->discountType, $this->discountValue);
    }

    public function validityPeriod(): VoucherValidityPeriod
    {
        return new VoucherValidityPeriod($this->startsAt, $this->endsAt);
    }

    public function recipientConstraint(): VoucherRecipientConstraint
    {
        return new VoucherRecipientConstraint($this->recipientUserId, $this->recipientEmail);
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
