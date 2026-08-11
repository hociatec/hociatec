<?php

declare(strict_types=1);

namespace App\Module\Voucher\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\ValueObject\VoucherDiscount;
use App\Module\Voucher\Domain\ValueObject\VoucherRecipientConstraint;
use App\Module\Voucher\Domain\ValueObject\VoucherValidityPeriod;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vouchers')]
#[ORM\HasLifecycleCallbacks]
class Voucher
{
    use VoucherLifecycleTrait;

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
        $this->initializeTimestamps();
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
        $this->description = $this->normalizeOptionalText($description);

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
        $this->discountValue = (new VoucherDiscount($this->discountType, $discountValue))->value;

        return $this;
    }

    public function changeDiscount(string $discountType, int $discountValue): self
    {
        $this->applyDiscount(new VoucherDiscount($discountType, $discountValue));

        return $this;
    }

    public function getRecipientUserId(): ?int
    {
        return $this->recipientUserId;
    }

    public function setRecipientUserId(?int $recipientUserId): self
    {
        if (null !== $recipientUserId && $recipientUserId <= 0) {
            throw new \InvalidArgumentException('Destinataire invalide.');
        }

        $this->recipientUserId = $recipientUserId;

        return $this;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(?string $recipientEmail): self
    {
        $this->recipientEmail = $this->normalizeOptionalText($recipientEmail);

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
        return $this->isActive()
            && $this->validityPeriod()->hasStartedAt($now)
            && !$this->validityPeriod()->isExpiredAt($now)
            && $this->recipientConstraint()->matches($user);
    }

    public function canBeNotifiedTo(User $user, \DateTimeImmutable $now): bool
    {
        return $this->canBeUsedBy($user, $now);
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

    private function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    private function applyDiscount(VoucherDiscount $discount): void
    {
        $this->discountType = $discount->type;
        $this->discountValue = $discount->value;
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        $value = null !== $value ? trim($value) : null;

        return '' === $value ? null : $value;
    }
}
