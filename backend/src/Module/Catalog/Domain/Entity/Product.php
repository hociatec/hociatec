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

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?object $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageAlt = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage2Name', size: 'galleryImage2Size')]
    private ?object $galleryImage2File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage2Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage2Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage3Name', size: 'galleryImage3Size')]
    private ?object $galleryImage3File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage3Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage3Size = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'galleryImage4Name', size: 'galleryImage4Size')]
    private ?object $galleryImage4File = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $galleryImage4Name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $galleryImage4Size = null;

    private ?ProductGallery $gallery = null;

    #[ORM\ManyToOne(targetEntity: Brand::class)]
    #[ORM\JoinColumn(name: 'brand_id', nullable: true, onDelete: 'SET NULL')]
    private ?Brand $brandReference = null;

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

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }
    public function getSku(): string { return $this->sku; }
    public function setSku(string $sku): self { $this->sku = $sku; return $this; }
    public function getShortDescription(): ?string { return $this->shortDescription; }
    public function setShortDescription(?string $shortDescription): self { $this->shortDescription = $shortDescription; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getPriceCents(): int { return $this->pricing->priceCents(); }
    public function setPriceCents(int $priceCents): self { $this->pricing->changePrice($priceCents); return $this; }
    public function getSellingType(): string { return $this->pricing->sellingType(); }
    public function setSellingType(string $type): self { $this->pricing->changeSellingType($type); return $this; }
    public function getBrand(): ?string { return $this->brandReference?->getName(); }
    public function getBrandId(): ?int { return $this->brandReference?->getId(); }
    public function getBrandReference(): ?Brand { return $this->brandReference; }
    public function setBrandReference(?Brand $brand): self { $this->brandReference = $brand; return $this; }
    public function getVariantGroup(): ?string { return $this->characteristics->variantGroup(); }
    public function setVariantGroup(?string $variantGroup): self { $this->characteristics->changeVariantGroup($variantGroup); return $this; }

    public function getVariantPosition(): int { return $this->characteristics->variantPosition(); }
    public function setVariantPosition(int $variantPosition): self { $this->characteristics->changeVariantPosition($variantPosition); return $this; }
    public function getReleaseYear(): ?int { return $this->characteristics->releaseYear(); }
    public function setReleaseYear(?int $releaseYear): self { $this->characteristics->changeReleaseYear($releaseYear); return $this; }
    public function getStorageCapacity(): ?string { return $this->characteristics->storageCapacity(); }
    public function setStorageCapacity(?string $storageCapacity): self { $this->characteristics->changeStorageCapacity($storageCapacity); return $this; }
    public function getMemoryRam(): ?string { return $this->characteristics->memoryRam(); }
    public function setMemoryRam(?string $memoryRam): self { $this->characteristics->changeMemoryRam($memoryRam); return $this; }
    public function getColor(): ?string { return $this->characteristics->color(); }
    public function setColor(?string $color): self { $this->characteristics->changeColor($color); return $this; }
    public function getStock(): int { return $this->inventory->stock(); }
    public function setStock(int $stock): self { $this->inventory->changeStock($stock); return $this; }
    public function getLowStockThreshold(): int { return $this->inventory->lowStockThreshold(); }
    public function setLowStockThreshold(int $lowStockThreshold): self { $this->inventory->changeLowStockThreshold($lowStockThreshold); return $this; }
    public function isPublished(): bool { return $this->publication->isPublished(); }
    public function setIsPublished(bool $isPublished): self { $this->publication->changePublished($isPublished); return $this; }
    public function isFeaturedHome(): bool { return $this->publication->isFeaturedHome(); }

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

    public function setImageFile(?object $imageFile): self
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile || null !== $this->gallery()->imageName()) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFile(): ?object { return $this->imageFile; }
    public function getImageName(): ?string { return $this->gallery()->imageName(); }
    public function setImageName(?string $imageName): self { $this->gallery()->changeImageName($imageName); return $this; }
    public function getImageSize(): ?int { return $this->gallery()->imageSize(); }
    public function setImageSize(?int $imageSize): self { $this->gallery()->changeImageSize($imageSize); return $this; }
    public function getImageAlt(): ?string { return $this->gallery()->imageAlt(); }
    public function setImageAlt(?string $imageAlt): self { $this->gallery()->changeImageAlt($imageAlt); return $this; }

    /** @param 0|1|2|3 $position */
    public function setGalleryImageFile(int $position, ?object $file): self
    {
        match ($position) {
            0 => $this->changePrimaryGalleryImageFile($file),
            1 => $this->changeGalleryImage2File($file),
            2 => $this->changeGalleryImage3File($file),
            3 => $this->changeGalleryImage4File($file),
            default => throw new \InvalidArgumentException('Indice d\'image de galerie invalide.'),
        };

        return $this;
    }

    /** @param 0|1|2|3 $position */
    public function removeGalleryImage(int $position): self
    {
        match ($position) {
            0 => $this->imageFile = null,
            1 => $this->galleryImage2File = null,
            2 => $this->galleryImage3File = null,
            3 => $this->galleryImage4File = null,
            default => throw new \InvalidArgumentException('Indice d\'image de galerie invalide.'),
        };

        if ($this->gallery()->remove($position)) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /** @return list<string> */
    public function getGalleryImageNames(): array { return $this->gallery()->names(); }
    public function getGalleryImageNameByPosition(int $position): ?string { return $this->gallery()->nameByPosition($position); }
    public function getGalleryImage2File(): ?object { return $this->galleryImage2File; }
    public function getGalleryImage2Name(): ?string { return $this->gallery()->galleryImage2Name(); }
    public function setGalleryImage2Name(?string $galleryImage2Name): self { $this->gallery()->changeGalleryImage2Name($galleryImage2Name); return $this; }
    public function getGalleryImage2Size(): ?int { return $this->gallery()->galleryImage2Size(); }
    public function setGalleryImage2Size(?int $galleryImage2Size): self { $this->gallery()->changeGalleryImage2Size($galleryImage2Size); return $this; }
    public function getGalleryImage3File(): ?object { return $this->galleryImage3File; }
    public function getGalleryImage3Name(): ?string { return $this->gallery()->galleryImage3Name(); }
    public function setGalleryImage3Name(?string $galleryImage3Name): self { $this->gallery()->changeGalleryImage3Name($galleryImage3Name); return $this; }
    public function getGalleryImage3Size(): ?int { return $this->gallery()->galleryImage3Size(); }
    public function setGalleryImage3Size(?int $galleryImage3Size): self { $this->gallery()->changeGalleryImage3Size($galleryImage3Size); return $this; }
    public function getGalleryImage4File(): ?object { return $this->galleryImage4File; }
    public function getGalleryImage4Name(): ?string { return $this->gallery()->galleryImage4Name(); }
    public function setGalleryImage4Name(?string $galleryImage4Name): self { $this->gallery()->changeGalleryImage4Name($galleryImage4Name); return $this; }
    public function getGalleryImage4Size(): ?int { return $this->gallery()->galleryImage4Size(); }
    public function setGalleryImage4Size(?int $galleryImage4Size): self { $this->gallery()->changeGalleryImage4Size($galleryImage4Size); return $this; }

    private function changePrimaryGalleryImageFile(?object $file): void
    {
        $this->imageFile = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->imageName());
    }

    private function changeGalleryImage2File(?object $file): void
    {
        $this->galleryImage2File = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->galleryImage2Name());
    }

    private function changeGalleryImage3File(?object $file): void
    {
        $this->galleryImage3File = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->galleryImage3Name());
    }

    private function changeGalleryImage4File(?object $file): void
    {
        $this->galleryImage4File = $file;
        $this->touchWhenGalleryImageChanges($file, $this->gallery()->galleryImage4Name());
    }

    private function gallery(): ProductGallery
    {
        return $this->gallery ??= new ProductGallery(
            $this->imageName,
            $this->imageSize,
            $this->imageAlt,
            $this->galleryImage2Name,
            $this->galleryImage2Size,
            $this->galleryImage3Name,
            $this->galleryImage3Size,
            $this->galleryImage4Name,
            $this->galleryImage4Size,
        );
    }

    private function touchWhenGalleryImageChanges(?object $file, ?string $existingName): void
    {
        if (null !== $file || null !== $existingName) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getReviewsCount(): int { return $this->reviewsCount; }

    public function setReviewsCount(int $count): self
    {
        $this->reviewsCount = max(0, $count);

        return $this;
    }

    public function getReviewsAverage(): float { return $this->reviewsAverage; }

    public function setReviewsAverage(float $average): self
    {
        $this->reviewsAverage = max(0, $average);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

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
