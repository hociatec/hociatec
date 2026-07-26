<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Catalog\Entity\Product;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QuoteWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function delete(Quote $quote): void
    {
        $this->entityManager->remove($quote);
        $this->entityManager->flush();
    }

    public function setStatus(Quote $quote, string $status): void
    {
        $quote->setStatus($status);
        if (Quote::STATUS_SENT === $status && null === $quote->getCreatedEmailSentAt()) {
            $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }

    /** @param array<string,mixed> $payload */
    public function addProductItem(Quote $quote, Product $product, array $payload): void
    {
        $name = is_string($payload['name'] ?? null) && '' !== trim($payload['name'])
            ? trim($payload['name'])
            : $product->getName();
        $unitPriceCents = is_numeric($payload['unitPriceCents'] ?? null)
            ? (int) $payload['unitPriceCents']
            : $product->getPriceCents();

        $item = new QuoteItem($name, max(0, $unitPriceCents));
        $item
            ->setItemType(QuoteItem::TYPE_PRODUCT)
            ->setProductId($product->getId())
            ->setDescription($this->stringOrNull($payload['description'] ?? null))
            ->setUnit($this->stringOrNull($payload['unit'] ?? ('rental' === strtolower($product->getSellingType()) ? 'jour' : null)))
            ->setQuantity(max(1, (int) ($payload['quantity'] ?? 1)));

        if (isset($payload['vatRate'])) {
            $item->setVatRateBps((int) round(((float) $payload['vatRate']) * 100));
        } elseif (isset($payload['vatRateBps'])) {
            $item->setVatRateBps((int) $payload['vatRateBps']);
        }
        if (isset($payload['discountCents'])) {
            $item->setDiscountCents((int) $payload['discountCents']);
        }

        $quote->addItem($item);
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function save(Quote $quote): void
    {
        $this->entityManager->persist($quote);
        $this->entityManager->flush();
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
