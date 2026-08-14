<?php

declare(strict_types=1);

namespace App\Module\Favorite\Domain\Entity;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_favorites')]
class Favorite
{
    public const CATEGORY_PRODUCT = 'product';
    public const CATEGORY_SERVICE = 'service';
    public const CATEGORY_NEWS = 'news';
    public const CATEGORY_PODCAST = 'podcast';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 32)]
    private string $category;

    #[ORM\Column]
    private int $targetId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private ?Product $legacyProduct = null;

    public function __construct(User $user, string|Product $category, ?int $targetId = null)
    {
        $this->user = $user;
        if ($category instanceof Product) {
            $this->legacyProduct = $category;
            $this->category = self::CATEGORY_PRODUCT;
            $this->targetId = max(0, $category->getId() ?? 0);
        } else {
            $this->category = self::normalizeCategory($category);
            $this->targetId = max(1, $targetId ?? 0);
        }
        $this->createdAt = new \DateTimeImmutable();
    }

    /** @return list<string> */
    public static function categories(): array
    {
        return [
            self::CATEGORY_PRODUCT,
            self::CATEGORY_SERVICE,
            self::CATEGORY_NEWS,
            self::CATEGORY_PODCAST,
        ];
    }

    public static function normalizeCategory(string $category): string
    {
        $normalized = trim(mb_strtolower($category));

        return in_array($normalized, self::categories(), true)
            ? $normalized
            : self::CATEGORY_PRODUCT;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function getProduct(): ?Product
    {
        return $this->legacyProduct;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
