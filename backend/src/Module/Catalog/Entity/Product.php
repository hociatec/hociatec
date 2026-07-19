<?php

declare(strict_types=1);

namespace App\Module\Catalog\Entity;

use App\Module\Catalog\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'catalog_products')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Product
{
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

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage2Name', size: 'galleryImage2Size')]
    private ?File $galleryImage2File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage2Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage2Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage3Name', size: 'galleryImage3Size')]
    private ?File $galleryImage3File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage3Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage3Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage4Name', size: 'galleryImage4Size')]
    private ?File $galleryImage4File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage4Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage4Size = null;

    #[ORM\Column(length: 10, options: ['default' => 'sale'])]
    private string $sellingType = 'sale'; // 'sale' or 'rental'

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $reviewsCount = 0;

    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private float $reviewsAverage = 0.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    // Discount fields
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $discountType = null; // 'percent' or 'fixed_cents'

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $discountValue = null; // percent value or cents depending on type

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $discountStartsAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $discountEndsAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $discountEnabled = false;

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

        $now = new DateTimeImmutable();
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
        $normalized = $variantGroup !== null ? trim($variantGroup) : null;
        $this->variantGroup = $normalized !== '' ? $normalized : null;

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
        if ($releaseYear !== null && ($releaseYear < 2000 || $releaseYear > 2100)) {
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
        $normalized = $storageCapacity !== null ? trim($storageCapacity) : null;
        $this->storageCapacity = $normalized !== '' ? $normalized : null;

        return $this;
    }

    public function getMemoryRam(): ?string
    {
        return $this->memoryRam;
    }

    public function setMemoryRam(?string $memoryRam): self
    {
        $normalized = $memoryRam !== null ? trim($memoryRam) : null;
        $this->memoryRam = $normalized !== '' ? $normalized : null;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $normalized = $color !== null ? trim($color) : null;
        $this->color = $normalized !== '' ? $normalized : null;

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
        if ($category === null) {
            throw new \InvalidArgumentException('Le produit doit etre rattache a une categorie.');
        }

        $this->category = $category;

        return $this;
    }

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null || $this->imageName !== null) {
            $this->updatedAt = new DateTimeImmutable();
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

    public function isDiscountEnabled(): bool
    {
        return $this->discountEnabled;
    }

    public function setDiscountEnabled(bool $enabled): self
    {
        $this->discountEnabled = $enabled;
        return $this;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function setDiscountType(?string $type): self
    {
        if ($type !== null && !in_array($type, ['percent', 'fixed_cents'], true)) {
            throw new \InvalidArgumentException('Type de remise invalide.');
        }
        $this->discountType = $type;
        return $this;
    }

    public function getDiscountValue(): ?int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(?int $value): self
    {
        if ($value !== null && $value < 0) {
            throw new \InvalidArgumentException('Valeur de remise invalide.');
        }
        $this->discountValue = $value;
        return $this;
    }

    public function getDiscountStartsAt(): ?DateTimeImmutable
    {
        return $this->discountStartsAt;
    }

    public function setDiscountStartsAt(?DateTimeImmutable $date): self
    {
        $this->discountStartsAt = $date;
        return $this;
    }

    public function getDiscountEndsAt(): ?DateTimeImmutable
    {
        return $this->discountEndsAt;
    }

    public function setDiscountEndsAt(?DateTimeImmutable $date): self
    {
        $this->discountEndsAt = $date;
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

    public function getEffectivePriceCents(?DateTimeImmutable $now = null): int
    {
        $now = $now ?? new DateTimeImmutable();
        $base = $this->getPriceCents();
        if ($this->discountEnabled !== true) {
            return $base;
        }
        if ($this->discountStartsAt !== null && $now < $this->discountStartsAt) {
            return $base;
        }
        if ($this->discountEndsAt !== null && $now > $this->discountEndsAt) {
            return $base;
        }
        if ($this->discountType === 'percent' && $this->discountValue !== null) {
            $percent = max(0, min(100, $this->discountValue));
            $disc = (int) round($base * ($percent / 100));
            return max(0, $base - $disc);
        }
        if ($this->discountType === 'fixed_cents' && $this->discountValue !== null) {
            return max(0, $base - $this->discountValue);
        }
        return $base;
    }

    public function getGalleryImage2File(): ?File
    {
        return $this->galleryImage2File;
    }

    public function getGalleryImage2Name(): ?string
    {
        return $this->galleryImage2Name;
    }

    public function setGalleryImage2Name(?string $galleryImage2Name): self
    {
        $this->galleryImage2Name = $galleryImage2Name;

        return $this;
    }

    public function getGalleryImage2Size(): ?int
    {
        return $this->galleryImage2Size;
    }

    public function setGalleryImage2Size(?int $galleryImage2Size): self
    {
        $this->galleryImage2Size = $galleryImage2Size;

        return $this;
    }

    public function getGalleryImage3File(): ?File
    {
        return $this->galleryImage3File;
    }

    public function getGalleryImage3Name(): ?string
    {
        return $this->galleryImage3Name;
    }

    public function setGalleryImage3Name(?string $galleryImage3Name): self
    {
        $this->galleryImage3Name = $galleryImage3Name;

        return $this;
    }

    public function getGalleryImage3Size(): ?int
    {
        return $this->galleryImage3Size;
    }

    public function setGalleryImage3Size(?int $galleryImage3Size): self
    {
        $this->galleryImage3Size = $galleryImage3Size;

        return $this;
    }

    public function getGalleryImage4File(): ?File
    {
        return $this->galleryImage4File;
    }

    public function getGalleryImage4Name(): ?string
    {
        return $this->galleryImage4Name;
    }

    public function setGalleryImage4Name(?string $galleryImage4Name): self
    {
        $this->galleryImage4Name = $galleryImage4Name;

        return $this;
    }

    public function getGalleryImage4Size(): ?int
    {
        return $this->galleryImage4Size;
    }

    public function setGalleryImage4Size(?int $galleryImage4Size): self
    {
        $this->galleryImage4Size = $galleryImage4Size;

        return $this;
    }

    /**
     * @param 0|1|2|3 $position
     */
    public function setGalleryImageFile(int $position, ?File $file): self
    {
        if ($position === 0) {
            return $this->setImageFile($file);
        }

        switch ($position) {
            case 1:
                $this->galleryImage2File = $file;
                if ($file !== null || $this->galleryImage2Name !== null) {
                    $this->updatedAt = new DateTimeImmutable();
                }
                break;
            case 2:
                $this->galleryImage3File = $file;
                if ($file !== null || $this->galleryImage3Name !== null) {
                    $this->updatedAt = new DateTimeImmutable();
                }
                break;
            case 3:
                $this->galleryImage4File = $file;
                if ($file !== null || $this->galleryImage4Name !== null) {
                    $this->updatedAt = new DateTimeImmutable();
                }
                break;
            default:
                throw new \InvalidArgumentException('Indice d\'image de galerie invalide.');
        }

        return $this;
    }

    /**
     * @param 0|1|2|3 $position
     */
    public function removeGalleryImage(int $position): self
    {
        switch ($position) {
            case 0:
                $this
                    ->setImageFile(null)
                    ->setImageName(null)
                    ->setImageSize(null)
                    ->setImageAlt(null);
                break;
            case 1:
                if ($this->galleryImage2Name !== null) {
                    $this->galleryImage2Name = null;
                    $this->galleryImage2Size = null;
                    $this->updatedAt = new DateTimeImmutable();
                }
                $this->galleryImage2File = null;
                break;
            case 2:
                if ($this->galleryImage3Name !== null) {
                    $this->galleryImage3Name = null;
                    $this->galleryImage3Size = null;
                    $this->updatedAt = new DateTimeImmutable();
                }
                $this->galleryImage3File = null;
                break;
            case 3:
                if ($this->galleryImage4Name !== null) {
                    $this->galleryImage4Name = null;
                    $this->galleryImage4Size = null;
                    $this->updatedAt = new DateTimeImmutable();
                }
                $this->galleryImage4File = null;
                break;
            default:
                throw new \InvalidArgumentException('Indice d\'image de galerie invalide.');
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getGalleryImageNames(): array
    {
        $names = [];

        foreach ([0, 1, 2, 3] as $position) {
            $name = $this->getGalleryImageNameByPosition($position);
            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function getGalleryImageNameByPosition(int $position): ?string
    {
        return match ($position) {
            0 => $this->imageName,
            1 => $this->galleryImage2Name,
            2 => $this->galleryImage3Name,
            3 => $this->galleryImage4Name,
            default => null,
        };
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
