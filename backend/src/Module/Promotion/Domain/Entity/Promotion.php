<?php

declare(strict_types=1);

namespace App\Module\Promotion\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'promotions')]
#[ORM\HasLifecycleCallbacks]
class Promotion
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED_CENTS = 'fixed_cents';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 140)]
    private string $name;

    #[ORM\Column(length: 140, unique: true)]
    private string $slug;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $discountType = self::TYPE_PERCENT;

    #[ORM\Column(type: 'integer')]
    private int $discountValue = 0;

    #[ORM\Column(length: 60)]
    private string $audienceKey = 'all_users';

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $criteria = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $criteria
     */
    public function __construct(
        string $name,
        string $slug,
        string $discountType,
        int $discountValue,
        string $audienceKey,
        array $criteria = [],
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->discountType = $discountType;
        $this->discountValue = max(0, $discountValue);
        $this->audienceKey = $audienceKey;
        $this->criteria = $criteria;
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
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): self
    {
        $this->discountType = $discountType;

        return $this;
    }

    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(int $discountValue): self
    {
        $this->discountValue = max(0, $discountValue);

        return $this;
    }

    public function getAudienceKey(): string
    {
        return $this->audienceKey;
    }

    public function setAudienceKey(string $audienceKey): self
    {
        $this->audienceKey = $audienceKey;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function setCriteria(array $criteria): self
    {
        $this->criteria = $criteria;

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
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

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
}
