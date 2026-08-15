<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\DTO;

use App\Module\Catalog\Domain\Entity\Product;

final readonly class ProductVariantCopyData
{
    public Product $template;
    public string $baseName;
    public string $baseSku;
    public ?string $baseSlug;
    public string $variantGroup;
    /** @var list<array{code:string,label:string,value:string}> */
    public array $attributes;
    public int $stock;
    public int $priceCents;
    public ?int $salePriceCents;
    public ?int $rentalPriceCents;
    public bool $availableForSale;
    public bool $availableForRental;
    public int $position;

    /**
     * @param array{
     *   template?: Product,
     *   baseName?: string,
     *   baseSku?: string,
     *   baseSlug?: ?string,
     *   variantGroup?: string,
     *   attributes?: list<array{code:string,label:string,value:string}>,
     *   stock?: int,
     *   priceCents?: ?int,
     *   salePriceCents?: ?int,
     *   rentalPriceCents?: ?int,
     *   position?: int
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        /** @var array{
         *   template: ?Product,
         *   baseName: string,
         *   baseSku: string,
         *   baseSlug: ?string,
         *   variantGroup: string,
         *   attributes: list<array{code:string,label:string,value:string}>,
         *   stock: int,
         *   priceCents: ?int,
         *   salePriceCents: ?int,
         *   rentalPriceCents: ?int,
         *   position: int
         * } $data
         */
        $data = array_replace([
            'template' => null,
            'baseName' => '',
            'baseSku' => '',
            'baseSlug' => null,
            'variantGroup' => '',
            'attributes' => [],
            'stock' => 0,
            'priceCents' => null,
            'salePriceCents' => null,
            'rentalPriceCents' => null,
            'position' => 1,
        ], $payload ?? []);

        if (!$data['template'] instanceof Product) {
            throw new \InvalidArgumentException('Le produit modèle est obligatoire.');
        }
        $this->template = $data['template'];
        $this->baseName = (string) $data['baseName'];
        $this->baseSku = (string) $data['baseSku'];
        $this->baseSlug = $data['baseSlug'];
        $this->variantGroup = (string) $data['variantGroup'];
        $this->attributes = is_array($data['attributes']) ? array_values($data['attributes']) : [];
        $this->stock = (int) $data['stock'];
        $this->priceCents = null !== $data['priceCents'] ? (int) $data['priceCents'] : $this->template->getPriceCents();
        $this->availableForSale = $this->template->isAvailableForSale();
        $this->availableForRental = $this->template->isAvailableForRental();
        $this->salePriceCents = $this->availableForSale
            ? (null !== $data['salePriceCents']
                ? (int) $data['salePriceCents']
                : (null !== $data['priceCents'] ? (int) $data['priceCents'] : $this->template->getSalePriceCents()))
            : null;
        $this->rentalPriceCents = $this->availableForRental
            ? (null !== $data['rentalPriceCents']
                ? (int) $data['rentalPriceCents']
                : (null !== $data['priceCents'] ? (int) $data['priceCents'] : $this->template->getRentalPriceCents()))
            : null;
        $this->position = (int) $data['position'];
    }
}
