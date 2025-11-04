<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;
use App\Module\Quote\Entity\Service as QuoteServiceEntity;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuoteService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuoteRepository $quoteRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly ProductRepository $productRepository,
        private readonly QuoteNumberGenerator $numberGenerator,
        private readonly QuoteCalculator $calculator,
    ) {
    }

    public function createEmpty(): Quote
    {
        $quote = new Quote($this->numberGenerator->generate());
        $this->em->persist($quote);
        $this->em->flush();
        return $quote;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createFromPayload(array $payload): Quote
    {
        $quote = new Quote($this->numberGenerator->generate());
        $this->hydrateQuote($quote, $payload);
        $this->em->persist($quote);
        $this->em->flush();
        return $quote;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateFromPayload(Quote $quote, array $payload): Quote
    {
        $this->hydrateQuote($quote, $payload, true);
        $this->em->flush();
        return $quote;
    }

    public function duplicate(Quote $source): Quote
    {
        $copy = new Quote($this->numberGenerator->generate());
        $copy->setStatus(Quote::STATUS_DRAFT)
            ->setCustomerName($source->getCustomerName())
            ->setCustomerEmail($source->getCustomerEmail())
            ->setCustomerCompany($source->getCustomerCompany())
            ->setCustomerAddress($source->getCustomerAddress())
            ->setGlobalDiscountCents($source->getGlobalDiscountCents())
            ->setShippingCents($source->getShippingCents())
            ->setConditions($source->getConditions());

        foreach ($source->getItems() as $item) {
            $new = new QuoteItem($item->getName(), $item->getUnitPriceCents());
            $new->setItemType($item->getItemType())
                ->setProductId($item->getProductId())
                ->setServiceId($item->getServiceId())
                ->setDescription($item->getDescription())
                ->setUnit($item->getUnit())
                ->setQuantity($item->getQuantity())
                ->setVatRateBps($item->getVatRateBps())
                ->setDiscountCents($item->getDiscountCents());
            $copy->addItem($new);
        }

        $this->em->persist($copy);
        $this->em->flush();

        return $copy;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateQuote(Quote $quote, array $payload, bool $clearItems = false): void
    {
        $quote->setCustomerName(self::strOrNull($payload['customer']['name'] ?? null));
        $quote->setCustomerEmail(self::strOrNull($payload['customer']['email'] ?? null));
        $quote->setCustomerCompany(self::strOrNull($payload['customer']['company'] ?? null));
        $quote->setCustomerAddress(self::strOrNull($payload['customer']['address'] ?? null));

        $status = (string) ($payload['status'] ?? Quote::STATUS_DRAFT);
        $quote->setStatus($status);

        $quote->setGlobalDiscountCents((int) ($payload['discountCents'] ?? 0));
        $quote->setShippingCents((int) ($payload['shippingCents'] ?? 0));
        $quote->setConditions(self::strOrNull($payload['conditions'] ?? null));

        if ($clearItems) {
            foreach ($quote->getItems() as $existing) {
                $quote->removeItem($existing);
                $this->em->remove($existing);
            }
        }

        $items = $payload['items'] ?? [];
        if (is_array($items)) {
            foreach ($items as $raw) {
                $item = $this->buildItemFromPayload($raw);
                $quote->addItem($item);
                $this->em->persist($item);
            }
        }
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function buildItemFromPayload(array $raw): QuoteItem
    {
        $name = (string) ($raw['name'] ?? '');
        $unitPriceCents = isset($raw['unitPriceCents']) ? (int) $raw['unitPriceCents'] : null;

        // If a productId is provided, fill defaults from product when missing
        if (isset($raw['productId'])) {
            $product = $this->productRepository->find((int) $raw['productId']);
            if ($product !== null) {
                if ($name === '') { $name = $product->getName(); }
                if ($unitPriceCents === null) { $unitPriceCents = $product->getPriceCents(); }
                // Default unit for rental products if not provided
                if (!isset($raw['unit']) && strtolower($product->getSellingType()) === 'rental') {
                    $raw['unit'] = 'jour';
                }
                // Default type to product if not provided
                if (!isset($raw['type'])) { $raw['type'] = QuoteItem::TYPE_PRODUCT; }
            }
        }

        if ($name === '') { $name = 'Ligne'; }
        if ($unitPriceCents === null) { $unitPriceCents = 0; }

        $item = new QuoteItem($name, $unitPriceCents);
        $item->setItemType((string) ($raw['type'] ?? QuoteItem::TYPE_CUSTOM));
        $item->setDescription(self::strOrNull($raw['description'] ?? null));
        $item->setUnit(self::strOrNull($raw['unit'] ?? null));
        $item->setQuantity((int) ($raw['quantity'] ?? 1));

        if (isset($raw['vatRate'])) {
            // vatRate provided as percentage (e.g. 20 or 20.0)
            $item->setVatRateBps((int) round(((float) $raw['vatRate']) * 100));
        } elseif (isset($raw['vatRateBps'])) {
            $item->setVatRateBps((int) $raw['vatRateBps']);
        }

        $item->setDiscountCents((int) ($raw['discountCents'] ?? 0));

        // Optional link to product/service
        if (isset($raw['productId'])) {
            $item->setProductId((int) $raw['productId']);
        }
        if (isset($raw['serviceId'])) {
            $item->setServiceId((int) $raw['serviceId']);
        }

        return $item;
    }

    public function delete(Quote $quote): void
    {
        $this->em->remove($quote);
        $this->em->flush();
    }

    public function computeTotals(Quote $quote): array
    {
        return $this->calculator->computeTotals($quote);
    }

    public static function strOrNull(mixed $v): ?string
    {
        if ($v === null) { return null; }
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }
}
