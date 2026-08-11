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
    public ?string $color;
    public ?string $storageCapacity;
    public int $stock;
    public int $priceCents;
    public int $position;

    /**
     * @param array{
     *   template?: Product,
     *   baseName?: string,
     *   baseSku?: string,
     *   baseSlug?: ?string,
     *   variantGroup?: string,
     *   color?: ?string,
     *   storageCapacity?: ?string,
     *   stock?: int,
     *   priceCents?: ?int,
     *   position?: int
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'template' => null,
            'baseName' => '',
            'baseSku' => '',
            'baseSlug' => null,
            'variantGroup' => '',
            'color' => null,
            'storageCapacity' => null,
            'stock' => 0,
            'priceCents' => null,
            'position' => 1,
        ], $payload ?? []);

        $this->template = $data['template'];
        $this->baseName = (string) $data['baseName'];
        $this->baseSku = (string) $data['baseSku'];
        $this->baseSlug = $data['baseSlug'];
        $this->variantGroup = (string) $data['variantGroup'];
        $this->color = $data['color'];
        $this->storageCapacity = $data['storageCapacity'];
        $this->stock = (int) $data['stock'];
        $this->priceCents = null !== $data['priceCents'] ? (int) $data['priceCents'] : $this->template->getPriceCents();
        $this->position = (int) $data['position'];
    }
}
