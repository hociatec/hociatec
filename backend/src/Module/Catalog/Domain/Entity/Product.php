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
#[ORM\Index(name: 'idx_catalog_products_sale_price_publication', columns: ['is_published', 'sale_price_cents'])]
#[ORM\Index(name: 'idx_catalog_products_rental_price_publication', columns: ['is_published', 'rental_price_cents'])]
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

    #[ORM\Column(name: 'price_cents', type: 'integer', nullable: true)]
    private ?int $legacyPriceCents = null;

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

    public function __construct(string $name, string $slug, string $sku, string $description, ?int $salePriceCents, mixed ...$configuration)
    {
        [
            'rentalPriceCents' => $resolvedRentalPriceCents,
            'availableForSale' => $resolvedAvailableForSale,
            'availableForRental' => $resolvedAvailableForRental,
            'stock' => $resolvedStock,
            'category' => $resolvedCategory,
        ] = $this->resolvePricingConfiguration($salePriceCents, $configuration);

        if (!$resolvedCategory instanceof Category) {
            throw new \InvalidArgumentException('La categorie du produit est obligatoire.');
        }

        $this->name = $name;
        $this->slug = $slug;
        $this->sku = $sku;
        $this->description = $description;
        $this->pricing = new ProductPricing(
            $salePriceCents,
            $resolvedRentalPriceCents,
            $resolvedAvailableForSale,
            $resolvedAvailableForRental,
        );
        $this->legacyPriceCents = $salePriceCents ?? $resolvedRentalPriceCents;
        $this->inventory = new ProductInventory($resolvedStock);
        $this->publication = new ProductPublication();
        $this->characteristics = new ProductCharacteristics();
        $this->category = $resolvedCategory;

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

    public function getSalePriceCents(): ?int
    {
        return $this->pricing->salePriceCents();
    }

    public function setSalePriceCents(?int $priceCents): self
    {
        $this->pricing->changeSalePrice($priceCents);
        $this->syncLegacyPriceCents();

        return $this;
    }

    public function getRentalPriceCents(): ?int
    {
        return $this->pricing->rentalPriceCents();
    }

    public function setRentalPriceCents(?int $priceCents): self
    {
        $this->pricing->changeRentalPrice($priceCents);
        $this->syncLegacyPriceCents();

        return $this;
    }

    public function isAvailableForSale(): bool
    {
        return $this->pricing->availableForSale();
    }

    public function isAvailableForRental(): bool
    {
        return $this->pricing->availableForRental();
    }

    public function setAvailability(bool $availableForSale, bool $availableForRental): self
    {
        $this->pricing->changeAvailability($availableForSale, $availableForRental);
        $this->syncLegacyPriceCents();

        return $this;
    }

    public function supportsSellingType(ProductSellingType|string $type): bool
    {
        return $this->pricing->supports($type);
    }

    public function getUnitPriceCentsForSellingType(ProductSellingType|string $type): int
    {
        return $this->pricing->unitPriceFor($type);
    }

    public function resolveDisplaySellingType(?string $preferred = null): ProductSellingType
    {
        if (null !== $preferred && $this->supportsSellingType($preferred)) {
            return ProductSellingType::fromInput($preferred);
        }

        if ($this->isAvailableForSale()) {
            return ProductSellingType::Sale;
        }

        if ($this->isAvailableForRental()) {
            return ProductSellingType::Rental;
        }

        throw new \LogicException('Le produit n\'est disponible dans aucun mode.');
    }

    /** @return list<string> */
    public function getAvailableSellingTypes(): array
    {
        return array_values(array_filter([
            $this->isAvailableForSale() ? ProductSellingType::Sale->value : null,
            $this->isAvailableForRental() ? ProductSellingType::Rental->value : null,
        ]));
    }

    public function isMixedSellingMode(): bool
    {
        return $this->isAvailableForSale() && $this->isAvailableForRental();
    }

    public function sellingTypeLabelFor(ProductSellingType|string $type): string
    {
        return ProductSellingType::label($type);
    }

    public function priceUnitLabelFor(ProductSellingType|string $type): ?string
    {
        return ProductSellingType::priceUnitLabel($type);
    }

    public function getDisplayPriceCents(?string $preferred = null): int
    {
        return $this->getUnitPriceCentsForSellingType($this->resolveDisplaySellingType($preferred));
    }

    public function getPriceCents(): int
    {
        return $this->getDisplayPriceCents();
    }

    public function setPriceCents(int $priceCents): self
    {
        if ($this->isAvailableForSale() && !$this->isAvailableForRental()) {
            return $this->setSalePriceCents($priceCents);
        }

        if ($this->isAvailableForRental() && !$this->isAvailableForSale()) {
            return $this->setRentalPriceCents($priceCents);
        }

        return $this->setSalePriceCents($priceCents);
    }

    public function getDisplaySellingType(?string $preferred = null): string
    {
        return $this->resolveDisplaySellingType($preferred)->value;
    }

    public function getSellingType(): string
    {
        return $this->getDisplaySellingType();
    }

    public function setSellingType(ProductSellingType|string $type): self
    {
        $mode = ProductSellingType::fromInput($type);

        if (ProductSellingType::Sale === $mode && !$this->isAvailableForSale() && null === $this->getSalePriceCents() && null !== $this->getRentalPriceCents()) {
            $this->setSalePriceCents($this->getRentalPriceCents());
        }

        if (ProductSellingType::Rental === $mode && !$this->isAvailableForRental() && null === $this->getRentalPriceCents() && null !== $this->getSalePriceCents()) {
            $this->setRentalPriceCents($this->getSalePriceCents());
        }

        return match ($mode) {
            ProductSellingType::Sale => $this->setAvailability(true, false),
            ProductSellingType::Rental => $this->setAvailability(false, true),
        };
    }

    public function getDisplaySellingTypeLabel(?string $preferred = null): string
    {
        return $this->sellingTypeLabelFor($this->resolveDisplaySellingType($preferred));
    }

    public function getDisplayPriceUnitLabel(?string $preferred = null): ?string
    {
        return $this->priceUnitLabelFor($this->resolveDisplaySellingType($preferred));
    }

    /** @return array{sellingType:string,sellingTypeLabel:string,priceUnitLabel:?string,priceCents:int} */
    public function getSellingTypeContext(?string $preferred = null): array
    {
        $type = $this->resolveDisplaySellingType($preferred);

        return [
            'sellingType' => $type->value,
            'sellingTypeLabel' => $this->sellingTypeLabelFor($type),
            'priceUnitLabel' => $this->priceUnitLabelFor($type),
            'priceCents' => $this->getUnitPriceCentsForSellingType($type),
        ];
    }

    public function getEffectivePriceCentsForSellingType(ProductSellingType|string $type, ?\DateTimeImmutable $now = null): int
    {
        return $this->discount()->effectivePriceCents($this->getUnitPriceCentsForSellingType($type), $now);
    }

    public function getDisplayEffectivePriceCents(?string $preferred = null, ?\DateTimeImmutable $now = null): int
    {
        return $this->getEffectivePriceCentsForSellingType($this->resolveDisplaySellingType($preferred), $now);
    }

    public function getListPriceCentsForSellingType(ProductSellingType|string $type): ?int
    {
        $mode = ProductSellingType::fromInput($type);

        return match ($mode) {
            ProductSellingType::Sale => $this->getSalePriceCents(),
            ProductSellingType::Rental => $this->getRentalPriceCents(),
        };
    }

    public function getSellingModeLabel(): string
    {
        if ($this->isMixedSellingMode()) {
            return 'Vente et location';
        }

        return $this->getDisplaySellingTypeLabel();
    }

    public function getLegacyPriceCents(): int
    {
        return $this->legacyPriceCents ?? $this->getDisplayPriceCents();
    }

    public function getLegacySellingType(): string
    {
        return $this->getDisplaySellingType();
    }

    public function getLegacyEffectivePriceCents(?\DateTimeImmutable $now = null): int
    {
        return $this->getDisplayEffectivePriceCents(null, $now);
    }

    public function getLegacyPriceUnitLabel(): ?string
    {
        return $this->getDisplayPriceUnitLabel();
    }

    public function getLegacySellingTypeLabel(): string
    {
        return $this->getDisplaySellingTypeLabel();
    }

    public function setLegacySellingType(ProductSellingType|string $type): self
    {
        $mode = ProductSellingType::fromInput($type);
        $this->setAvailability(ProductSellingType::Sale === $mode, ProductSellingType::Rental === $mode);

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

    /**
     * @param array<int|string, mixed> $configuration
     *
     * @return array{rentalPriceCents:?int,availableForSale:bool,availableForRental:bool,stock:int,category:?Category}
     */
    private function resolvePricingConfiguration(?int $salePriceCents, array $configuration): array
    {
        $rentalPriceCentsOrStock = $configuration[0] ?? null;
        $availableForSaleOrCategory = $configuration[1] ?? null;

        if ($availableForSaleOrCategory instanceof Category) {
            return [
                'rentalPriceCents' => $salePriceCents,
                'availableForSale' => true,
                'availableForRental' => false,
                'stock' => is_int($rentalPriceCentsOrStock) ? $rentalPriceCentsOrStock : 0,
                'category' => $availableForSaleOrCategory,
            ];
        }

        return [
            'rentalPriceCents' => is_int($rentalPriceCentsOrStock) ? $rentalPriceCentsOrStock : null,
            'availableForSale' => (bool) ($configuration[1] ?? false),
            'availableForRental' => (bool) ($configuration[2] ?? false),
            'stock' => is_int($configuration[3] ?? null) ? $configuration[3] : 0,
            'category' => $configuration[4] instanceof Category ? $configuration[4] : null,
        ];
    }

    private function syncLegacyPriceCents(): void
    {
        if ($this->isAvailableForSale() && null !== $this->getSalePriceCents()) {
            $this->legacyPriceCents = $this->getSalePriceCents();

            return;
        }

        if ($this->isAvailableForRental() && null !== $this->getRentalPriceCents()) {
            $this->legacyPriceCents = $this->getRentalPriceCents();

            return;
        }

        $this->legacyPriceCents = $this->getSalePriceCents() ?? $this->getRentalPriceCents();
    }
}
