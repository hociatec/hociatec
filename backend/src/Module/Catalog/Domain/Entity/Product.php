<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_products')]
#[ORM\Index(name: 'idx_catalog_products_publication', columns: ['is_published', 'is_featured_home', 'created_at'])]
#[ORM\Index(name: 'idx_catalog_products_category_publication', columns: ['category_id', 'is_published', 'created_at'])]
#[ORM\Index(name: 'idx_catalog_products_price_publication', columns: ['is_published', 'price_cents'])]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Product
{
    use ProductDiscountTrait;
    use ProductCharacteristicsStateTrait;
    use ProductGalleryStateTrait;
    use ProductGalleryInfoTrait;
    use ProductGalleryExtendedTrait;
    use ProductGalleryMutationsTrait;
    use ProductInventoryPublicationTrait;
    use ProductReviewStateTrait;

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

    // ?ProductGallery $gallery remains the composed gallery state owned by the entity.
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

    public function setSellingType(ProductSellingType|string $type): self
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
