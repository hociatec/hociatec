<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Catalog\Entity\Product;
use App\Module\Quote\DTO\QuoteItemAddition;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;

final readonly class QuoteWorkflowService
{
    public function __construct(
        private QuotePersistence $persistence,
    ) {
    }

    public function delete(Quote $quote): void
    {
        $this->persistence->delete($quote);
    }

    public function setStatus(Quote $quote, string $status): void
    {
        $quote->setStatus($status);
        if (Quote::STATUS_SENT === $status && null === $quote->getCreatedEmailSentAt()) {
            $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        }

        $this->persistence->flush();
    }

    public function addProductItem(Quote $quote, Product $product, QuoteItemAddition $input): void
    {
        $name = $input->name ?? $product->getName();
        $unitPriceCents = $input->unitPriceCents ?? $product->getPriceCents();

        $item = new QuoteItem($name, max(0, $unitPriceCents));
        $item
            ->setItemType(QuoteItem::TYPE_PRODUCT)
            ->setProductId($product->getId())
            ->setDescription($this->stringOrNull($input->description))
            ->setUnit($this->stringOrNull($input->unit ?? ('rental' === strtolower($product->getSellingType()) ? 'jour' : null)))
            ->setQuantity($input->quantity);

        if (null !== $input->vatRate) {
            $item->setVatRateBps((int) round($input->vatRate * 100));
        } elseif (null !== $input->vatRateBps) {
            $item->setVatRateBps($input->vatRateBps);
        }
        if (null !== $input->discountCents) {
            $item->setDiscountCents($input->discountCents);
        }

        $this->persistence->addItem($quote, $item);
        $this->persistence->flush();
    }

    public function save(Quote $quote): void
    {
        $this->persistence->save($quote);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim((string) $value);

        return '' === $normalized ? null : $normalized;
    }
}
