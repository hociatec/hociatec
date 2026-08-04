<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Service;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Quote\Application\DTO\QuoteItemPayload;
use App\Module\Quote\Domain\Entity\QuoteItem;

final readonly class QuoteItemFactory
{
    public function __construct(private ProductCatalogRepository $products)
    {
    }

    public function fromPayload(QuoteItemPayload $raw): QuoteItem
    {
        $name = $raw->name;
        $unitPriceCents = $raw->unitPriceCents;
        $unit = $raw->unit;
        $type = $raw->type ?? QuoteItem::TYPE_CUSTOM;

        if (null !== $raw->productId) {
            $product = $this->products->findProduct($raw->productId);
            if (null !== $product) {
                if ('' === $name) {
                    $name = $product->getName();
                }
                if (null === $unitPriceCents) {
                    $unitPriceCents = $product->getPriceCents();
                }
                if (null === $unit && 'rental' === strtolower($product->getSellingType())) {
                    $unit = 'jour';
                }
                if (null === $raw->type) {
                    $type = QuoteItem::TYPE_PRODUCT;
                }
            }
        }

        if ('' === $name) {
            $name = 'Ligne';
        }

        $item = new QuoteItem($name, $unitPriceCents ?? 0);
        $item->setItemType($type);
        $item->setDescription(QuoteValueNormalizer::strOrNull($raw->description));
        $item->setUnit(QuoteValueNormalizer::strOrNull($unit));
        $item->setQuantity($raw->quantity);
        $item->setVatRateBps($raw->vatRateBps);
        $item->setDiscountCents($raw->discountCents);
        $item->setProductId($raw->productId);
        $item->setServiceId($raw->serviceId);

        return $item;
    }
}
