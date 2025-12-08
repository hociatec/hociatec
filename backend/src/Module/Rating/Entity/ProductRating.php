<?php

declare(strict_types=1);

namespace App\Module\Rating\Entity;

use App\Module\Catalog\Entity\Product;
use App\Module\Comment\Entity\ProductComment;
use App\Module\Order\Entity\OrderItem;
use App\Module\Rating\Repository\ProductRatingRepository;
use App\Module\User\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRatingRepository::class)]
#[ORM\Table(name: 'product_ratings')]
#[ORM\HasLifecycleCallbacks]
class ProductRating
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\OneToOne(targetEntity: OrderItem::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private OrderItem $orderItem;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'smallint')]
    private int $score;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PUBLISHED;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\OneToOne(mappedBy: 'rating', targetEntity: ProductComment::class, cascade: ['persist', 'remove'])]
    private ?ProductComment $comment = null;

    public function __construct(Product $product, OrderItem $orderItem, User $user, int $score)
    {
        $this->product = $product;
        $this->orderItem = $orderItem;
        $this->user = $user;
        $this->score = $score;
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getOrderItem(): OrderItem
    {
        return $this->orderItem;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function publish(): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->publishedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getComment(): ?ProductComment
    {
        return $this->comment;
    }

    public function setComment(?ProductComment $comment): void
    {
        $this->comment = $comment;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
