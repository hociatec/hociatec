<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Workflow;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Quote\Application\DTO\QuoteItemAddition;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Shared\Application\UnitOfWork;
use App\Shared\Domain\ValueObject\DecimalNumber;

final readonly class QuoteWorkflowService
{
    public function __construct(
        private UnitOfWork $persistence,
    ) {
    }

    public function delete(Quote $quote): void
    {
        $this->persistence->remove($quote);
        $this->persistence->flush();
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
            $item->setVatRateBps(DecimalNumber::toScaledInt($input->vatRate, 2) ?? 0);
        } elseif (null !== $input->vatRateBps) {
            $item->setVatRateBps($input->vatRateBps);
        }
        if (null !== $input->discountCents) {
            $item->setDiscountCents($input->discountCents);
        }

        $quote->addItem($item);
        $this->persistence->persist($item);
        $this->persistence->flush();
    }

    public function save(Quote $quote): void
    {
        $this->persistence->persist($quote);
        $this->persistence->flush();
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
