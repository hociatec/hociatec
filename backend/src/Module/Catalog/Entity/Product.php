<?php

declare(strict_types=1);

namespace App\Module\Catalog\Entity;

use App\Module\Catalog\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'catalog_products')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Product
{
    use ProductDiscountTrait;
    use ProductGalleryTrait;

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

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    #[ORM\Column(type: 'integer')]
    private int $stock;

    #[ORM\Column(type: 'integer', options: ['default' => 3])]
    private int $lowStockThreshold = 3;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublished = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isFeaturedHome = false;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Category $category;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageAlt = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(name: 'brand_id', nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brandReference = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $variantGroup = null;

    #[ORM\Column(type: 'smallint', options: ['default' => 1])]
    private int $variantPosition = 1;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $releaseYear = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $storageCapacity = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $memoryRam = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(length: 10, options: ['default' => 'sale'])]
    private string $sellingType = 'sale'; // 'sale' or 'rental'

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
        $this->priceCents = $priceCents;
        $this->stock = $stock;
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
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): self
    {
        $this->priceCents = $priceCents;

        return $this;
    }

    public function getSellingType(): string
    {
        return $this->sellingType;
    }

    public function setSellingType(string $type): self
    {
        $type = strtolower($type);
        if (!in_array($type, ['sale', 'rental'], true)) {
            throw new \InvalidArgumentException('Type de vente/location invalide.');
        }
        $this->sellingType = $type;

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
        return $this->variantGroup;
    }

    public function setVariantGroup(?string $variantGroup): self
    {
        $normalized = null !== $variantGroup ? trim($variantGroup) : null;
        $this->variantGroup = '' !== $normalized ? $normalized : null;

        return $this;
    }

    public function getVariantPosition(): int
    {
        return $this->variantPosition;
    }

    public function setVariantPosition(int $variantPosition): self
    {
        if ($variantPosition < 1) {
            throw new \InvalidArgumentException('Position de variante invalide.');
        }

        $this->variantPosition = $variantPosition;

        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(?int $releaseYear): self
    {
        if (null !== $releaseYear && ($releaseYear < 2000 || $releaseYear > 2100)) {
            throw new \InvalidArgumentException('Année de modèle invalide.');
        }

        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function getStorageCapacity(): ?string
    {
        return $this->storageCapacity;
    }

    public function setStorageCapacity(?string $storageCapacity): self
    {
        $normalized = null !== $storageCapacity ? trim($storageCapacity) : null;
        $this->storageCapacity = '' !== $normalized ? $normalized : null;

        return $this;
    }

    public function getMemoryRam(): ?string
    {
        return $this->memoryRam;
    }

    public function setMemoryRam(?string $memoryRam): self
    {
        $normalized = null !== $memoryRam ? trim($memoryRam) : null;
        $this->memoryRam = '' !== $normalized ? $normalized : null;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $normalized = null !== $color ? trim($color) : null;
        $this->color = '' !== $normalized ? $normalized : null;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getLowStockThreshold(): int
    {
        return $this->lowStockThreshold;
    }

    public function setLowStockThreshold(int $lowStockThreshold): self
    {
        $this->lowStockThreshold = max(0, $lowStockThreshold);

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function isFeaturedHome(): bool
    {
        return $this->isFeaturedHome;
    }

    public function setIsFeaturedHome(bool $isFeaturedHome): self
    {
        $this->isFeaturedHome = $isFeaturedHome;

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

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile || null !== $this->imageName) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): self
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    public function getImageAlt(): ?string
    {
        return $this->imageAlt;
    }

    public function setImageAlt(?string $imageAlt): self
    {
        $this->imageAlt = $imageAlt;

        return $this;
    }

    public function getReviewsCount(): int
    {
        return $this->reviewsCount;
    }

    public function setReviewsCount(int $count): self
    {
        $this->reviewsCount = max(0, $count);

        return $this;
    }

    public function getReviewsAverage(): float
    {
        return $this->reviewsAverage;
    }

    public function setReviewsAverage(float $average): self
    {
        $this->reviewsAverage = max(0, $average);

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
