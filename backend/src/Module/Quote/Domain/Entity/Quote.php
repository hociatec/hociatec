<?php

declare(strict_types=1);

namespace App\Module\Quote\Domain\Entity;

use App\Module\Order\Domain\Entity\Order;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quotes')]
#[ORM\HasLifecycleCallbacks]
class Quote
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $number;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customerCompany = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerAddress = null;

    #[ORM\Column(type: 'integer')]
    private int $globalDiscountCents = 0;

    #[ORM\Column(type: 'integer')]
    private int $shippingCents = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $conditions = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;

    /** @var Collection<int, QuoteItem> */
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdEmailSentAt = null;

    #[ORM\OneToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'converted_order_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Order $convertedOrder = null;

    public function __construct(string $number)
    {
        $this->number = $number;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $name): self
    {
        $this->customerName = $name;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $email): self
    {
        $this->customerEmail = $email;

        return $this;
    }

    public function getCustomerCompany(): ?string
    {
        return $this->customerCompany;
    }

    public function setCustomerCompany(?string $company): self
    {
        $this->customerCompany = $company;

        return $this;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?string $address): self
    {
        $this->customerAddress = $address;

        return $this;
    }

    public function getGlobalDiscountCents(): int
    {
        return $this->globalDiscountCents;
    }

    public function setGlobalDiscountCents(int $cents): self
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('La remise globale ne peut pas être négative.');
        }

        $this->globalDiscountCents = $cents;

        return $this;
    }

    public function getShippingCents(): int
    {
        return $this->shippingCents;
    }

    public function setShippingCents(int $cents): self
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Les frais de livraison ne peuvent pas être négatifs.');
        }

        $this->shippingCents = $cents;

        return $this;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(?string $conditions): self
    {
        $this->conditions = $conditions;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeImmutable $validFrom): self
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /** @return Collection<int, QuoteItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(QuoteItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setQuote($this);
        }

        return $this;
    }

    public function removeItem(QuoteItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getQuote() === $this) {
                $item->setQuote(null);
            }
        }

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

    public function getCreatedEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->createdEmailSentAt;
    }

    public function setCreatedEmailSentAt(?\DateTimeImmutable $createdEmailSentAt): self
    {
        $this->createdEmailSentAt = $createdEmailSentAt;

        return $this;
    }

    public function getConvertedOrder(): ?Order
    {
        return $this->convertedOrder;
    }

    public function setConvertedOrder(?Order $convertedOrder): self
    {
        $this->convertedOrder = $convertedOrder;

        return $this;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
