<?php

declare(strict_types=1);

namespace App\Module\Comment\Domain\Entity;

use App\Module\Rating\Domain\Entity\ProductRating;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_comments')]
#[ORM\HasLifecycleCallbacks]
class ProductComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'comment', targetEntity: ProductRating::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductRating $rating;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'boolean')]
    private bool $isVisible = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(ProductRating $rating, string $body)
    {
        $this->rating = $rating;
        $this->body = $body;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ProductRating
    {
        return $this->rating;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $visible): self
    {
        $this->isVisible = $visible;

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
