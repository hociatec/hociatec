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
    public int $priceCents;
    public int $stock;
    public bool $isPublished;
    public bool $isFeaturedHome;
    public Category $category;
    public ?string $imageAlt;
    public string $sellingType;
    public ?Brand $brand;

    /**
     * @param array{
     *   name?: string,
     *   sku?: string,
     *   slug?: ?string,
     *   description?: string,
     *   shortDescription?: ?string,
     *   priceCents?: int,
     *   stock?: int,
     *   isPublished?: bool,
     *   isFeaturedHome?: bool,
     *   category?: Category,
     *   imageAlt?: ?string,
     *   sellingType?: string,
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
         *   priceCents: int,
         *   stock: int,
         *   isPublished: bool,
         *   isFeaturedHome: bool,
         *   category: ?Category,
         *   imageAlt: ?string,
         *   sellingType: string,
         *   brand: ?Brand
         * } $data
         */
        $data = array_replace([
            'name' => '',
            'sku' => '',
            'slug' => null,
            'description' => '',
            'shortDescription' => null,
            'priceCents' => 0,
            'stock' => 0,
            'isPublished' => false,
            'isFeaturedHome' => false,
            'category' => null,
            'imageAlt' => null,
            'sellingType' => 'sale',
            'brand' => null,
        ], $payload ?? []);

        $this->name = (string) $data['name'];
        $this->sku = (string) $data['sku'];
        $this->slug = $data['slug'];
        $this->description = (string) $data['description'];
        $this->shortDescription = $data['shortDescription'];
        $this->priceCents = (int) $data['priceCents'];
        $this->stock = (int) $data['stock'];
        $this->isPublished = (bool) $data['isPublished'];
        $this->isFeaturedHome = (bool) $data['isFeaturedHome'];
        if (!$data['category'] instanceof Category) {
            throw new \InvalidArgumentException('La catégorie du produit est obligatoire.');
        }
        $this->category = $data['category'];
        $this->imageAlt = $data['imageAlt'];
        $this->sellingType = (string) $data['sellingType'];
        $this->brand = $data['brand'];
    }
}
