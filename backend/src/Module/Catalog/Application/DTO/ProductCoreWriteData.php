<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;

final readonly class ProductCoreWriteData
{
    public string $name;
    public string $sku;
    public ?string $slug;
    public string $description;
    public ?string $shortDescription;
    public ?int $priceCents;
    public ?int $salePriceCents;
    public ?int $rentalPriceCents;
    public ?string $sellingType;
    public bool $availableForSale;
    public bool $availableForRental;
    public int $stock;
    public bool $isPublished;
    public bool $isFeaturedHome;
    public Category $category;
    public ?string $imageAlt;
    public ?Brand $brand;

    /**
     * @param array{
     *   name?: string,
     *   sku?: string,
     *   slug?: ?string,
     *   description?: string,
     *   shortDescription?: ?string,
     *   priceCents?: ?int,
     *   salePriceCents?: ?int,
     *   rentalPriceCents?: ?int,
     *   sellingType?: ?string,
     *   availableForSale?: bool,
     *   availableForRental?: bool,
     *   stock?: int,
     *   isPublished?: bool,
     *   isFeaturedHome?: bool,
     *   category?: Category,
     *   imageAlt?: ?string,
     *   brand?: ?Brand
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        /** @var array{
         *   name: string,
         *   sku: string,
         *   slug: ?string,
         *   description: string,
         *   shortDescription: ?string,
         *   salePriceCents: ?int,
         *   rentalPriceCents: ?int,
         *   availableForSale: bool,
         *   availableForRental: bool,
         *   stock: int,
         *   isPublished: bool,
         *   isFeaturedHome: bool,
         *   category: ?Category,
         *   imageAlt: ?string,
         *   brand: ?Brand
         * } $data
         */
        $data = array_replace([
            'name' => '',
            'sku' => '',
            'slug' => null,
            'description' => '',
            'shortDescription' => null,
            'priceCents' => null,
            'salePriceCents' => 0,
            'rentalPriceCents' => null,
            'sellingType' => null,
            'availableForSale' => true,
            'availableForRental' => false,
            'stock' => 0,
            'isPublished' => false,
            'isFeaturedHome' => false,
            'category' => null,
            'imageAlt' => null,
            'brand' => null,
        ], $payload ?? []);

        $this->name = (string) $data['name'];
        $this->sku = (string) $data['sku'];
        $this->slug = $data['slug'];
        $this->description = (string) $data['description'];
        $this->shortDescription = $data['shortDescription'];
        $sellingType = is_string($data['sellingType']) ? strtolower(trim($data['sellingType'])) : null;
        $legacyPriceCents = null !== $data['priceCents'] ? (int) $data['priceCents'] : null;
        $hasExplicitSalePrice = is_array($payload) && array_key_exists('salePriceCents', $payload);
        $hasExplicitRentalPrice = is_array($payload) && array_key_exists('rentalPriceCents', $payload);

        $this->priceCents = $legacyPriceCents;
        $this->sellingType = $sellingType;
        $this->salePriceCents = $hasExplicitSalePrice
            ? (int) $data['salePriceCents']
            : ('rental' === $sellingType ? null : $legacyPriceCents);
        $this->rentalPriceCents = $hasExplicitRentalPrice
            ? (int) $data['rentalPriceCents']
            : ('rental' === $sellingType ? $legacyPriceCents : null);
        $hasExplicitAvailability = is_array($payload)
            && (array_key_exists('availableForSale', $payload) || array_key_exists('availableForRental', $payload));
        $this->availableForSale = $hasExplicitAvailability ? (bool) $data['availableForSale'] : 'rental' !== $sellingType;
        $this->availableForRental = $hasExplicitAvailability ? (bool) $data['availableForRental'] : 'rental' === $sellingType;
        $this->stock = (int) $data['stock'];
        $this->isPublished = (bool) $data['isPublished'];
        $this->isFeaturedHome = (bool) $data['isFeaturedHome'];
        if (!$data['category'] instanceof Category) {
            throw new \InvalidArgumentException('La catégorie du produit est obligatoire.');
        }
        $this->category = $data['category'];
        $this->imageAlt = $data['imageAlt'];
        $this->brand = $data['brand'];

        if (!$this->availableForSale && !$this->availableForRental) {
            throw new \InvalidArgumentException('Le produit doit être disponible à la vente, à la location ou aux deux.');
        }

        if ($this->availableForSale && null === $this->salePriceCents) {
            throw new \InvalidArgumentException('Le prix de vente est obligatoire.');
        }

        if ($this->availableForRental && null === $this->rentalPriceCents) {
            throw new \InvalidArgumentException('Le prix mensuel de location est obligatoire.');
        }
    }
}
