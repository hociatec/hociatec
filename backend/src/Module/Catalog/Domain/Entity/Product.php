<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_products')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Product
{
    use ProductDiscountTrait;
    use ProductGalleryInfoTrait;
    use ProductGalleryExtendedTrait;
    use ProductGalleryMutationsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name;

    #[ORM\Column(length: 200, unique: true)]
    private string $slug;

    #[ORM\Column(length: 60, unique: true)]
    private string $sku;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Embedded(class: ProductPricing::class, columnPrefix: false)]
    private ProductPricing $pricing;

    #[ORM\Embedded(class: ProductInventory::class, columnPrefix: false)]
    private ProductInventory $inventory;

    #[ORM\Embedded(class: ProductPublication::class, columnPrefix: false)]
    private ProductPublication $publication;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Category $category;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(name: 'brand_id', nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brandReference = null;

    private ?ProductGallery $gallery = null;

    #[ORM\Embedded(class: ProductCharacteristics::class, columnPrefix: false)]
    private ProductCharacteristics $characteristics;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $reviewsCount = 0;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $reviewsAverage = 0.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $slug,
        string $sku,
        string $description,
        int $priceCents,
        int $stock,
        Category $category,
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->sku = $sku;
        $this->description = $description;
        $this->pricing = new ProductPricing($priceCents);
        $this->inventory = new ProductInventory($stock);
        $this->publication = new ProductPublication();
        $this->characteristics = new ProductCharacteristics();
        $this->category = $category;

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

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->pricing->priceCents();
    }

    public function setPriceCents(int $priceCents): self
    {
        $this->pricing->changePrice($priceCents);

        return $this;
    }

    public function getSellingType(): string
    {
        return $this->pricing->sellingType();
    }

    public function setSellingType(string $type): self
    {
        $this->pricing->changeSellingType($type);

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brandReference?->getName();
    }

    public function getBrandId(): ?int
    {
        return $this->brandReference?->getId();
    }

    public function getBrandReference(): ?Brand
    {
        return $this->brandReference;
    }

    public function setBrandReference(?Brand $brand): self
    {
        $this->brandReference = $brand;

        return $this;
    }

    public function getVariantGroup(): ?string
    {
        return $this->characteristics->variantGroup();
    }

    public function setVariantGroup(?string $variantGroup): self
    {
        $this->characteristics->changeVariantGroup($variantGroup);

        return $this;
    }

    public function getVariantPosition(): int
    {
        return $this->characteristics->variantPosition();
    }

    public function setVariantPosition(int $variantPosition): self
    {
        $this->characteristics->changeVariantPosition($variantPosition);

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->characteristics->releaseYear();
    }

    public function setReleaseYear(?int $releaseYear): self
    {
        $this->characteristics->changeReleaseYear($releaseYear);

        return $this;
    }

    public function getStorageCapacity(): ?string
    {
        return $this->characteristics->storageCapacity();
    }

    public function setStorageCapacity(?string $storageCapacity): self
    {
        $this->characteristics->changeStorageCapacity($storageCapacity);

        return $this;
    }

    public function getMemoryRam(): ?string
    {
        return $this->characteristics->memoryRam();
    }

    public function setMemoryRam(?string $memoryRam): self
    {
        $this->characteristics->changeMemoryRam($memoryRam);

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->characteristics->color();
    }

    public function setColor(?string $color): self
    {
        $this->characteristics->changeColor($color);

        return $this;
    }

    public function getStock(): int
    {
        return $this->inventory->stock();
    }

    public function setStock(int $stock): self
    {
        $this->inventory->changeStock($stock);

        return $this;
    }

    public function getLowStockThreshold(): int
    {
        return $this->inventory->lowStockThreshold();
    }

    public function setLowStockThreshold(int $lowStockThreshold): self
    {
        $this->inventory->changeLowStockThreshold($lowStockThreshold);

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->publication->isPublished();
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->publication->changePublished($isPublished);

        return $this;
    }

    public function isFeaturedHome(): bool
    {
        return $this->publication->isFeaturedHome();
    }

    public function setIsFeaturedHome(bool $isFeaturedHome): self
    {
        $this->publication->changeFeaturedHome($isFeaturedHome);

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        if (null === $category) {
            throw new \InvalidArgumentException('Le produit doit etre rattache a une categorie.');
        }

        $this->category = $category;

        return $this;
    }

    public function getReviewsCount(): int
    {
        return $this->reviewsCount;
    }

    public function setReviewsCount(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Le nombre d’avis ne peut pas être négatif.');
        }

        $this->reviewsCount = $count;

        return $this;
    }

    public function getReviewsAverage(): float
    {
        return $this->reviewsAverage;
    }

    public function setReviewsAverage(float $average): self
    {
        if ($average < 0) {
            throw new \InvalidArgumentException('La moyenne des avis ne peut pas être négative.');
        }

        $this->reviewsAverage = $average;

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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
