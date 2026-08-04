<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Service;

use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Quote\Application\DTO\QuoteItemPayload;
use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Domain\Exception\QuoteOperationException;

class QuoteService
{
    public const DEFAULT_CONDITIONS = "Le présent devis constitue une offre valable jusqu'à la date de fin de validité qui y figure. Il devient contractuel à compter de son acceptation expresse par le client.
Le devis est établi sur la base des informations communiquées par le client. Toute prestation, fourniture ou demande complémentaire non prévue au devis initial fera l'objet d'un accord écrit complémentaire ou d'un avenant.
Sauf stipulation particulière, les délais d'exécution ou de livraison sont indicatifs et courent à compter de la réception de l'acceptation du devis et, le cas échéant, de l'acompte prévu.
Sauf mention contraire, les prix sont exprimés en euros. Les taxes applicables sont celles en vigueur au jour de la facturation.
Pour les clients professionnels uniquement, tout retard de paiement pourra entraîner l'application de pénalités de retard exigibles sans rappel, calculées au taux de refinancement de la BCE majoré de 10 points, ainsi qu'une indemnité forfaitaire de 40 euros pour frais de recouvrement.
Pour les clients consommateurs, les garanties légales applicables demeurent celles prévues par la loi.";

    public function __construct(
        private readonly QuotePersistence $persistence,
        private readonly ProductRepository $productRepository,
        private readonly QuoteNumberGenerator $numberGenerator,
        private readonly QuoteCalculator $calculator,
        private readonly ?\DateTimeImmutable $today = null,
    ) {
    }

    public function createEmpty(): Quote
    {
        $quote = new Quote($this->numberGenerator->generate());
        try {
            $this->persistence->save($quote);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de créer le devis.', $exception);
        }

        return $quote;
    }

    public function createFromPayload(QuotePayload $payload): Quote
    {
        $quote = new Quote($this->numberGenerator->generate());
        $this->hydrateQuote($quote, $payload);
        try {
            $this->persistence->save($quote);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de créer le devis.', $exception);
        }

        return $quote;
    }

    public function updateFromPayload(Quote $quote, QuotePayload $payload): Quote
    {
        $this->hydrateQuote($quote, $payload, true);
        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de mettre à jour le devis.', $exception);
        }

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
            ->setConditions($source->getConditions())
            ->setValidFrom($source->getValidFrom())
            ->setValidUntil($source->getValidUntil());

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

        try {
            $this->persistence->save($copy);
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de dupliquer le devis.', $exception);
        }

        return $copy;
    }

    private function hydrateQuote(Quote $quote, QuotePayload $payload, bool $clearItems = false): void
    {
        $quote->setCustomerName(self::strOrNull($payload->customer['name'] ?? null));
        $quote->setCustomerEmail(self::strOrNull($payload->customer['email'] ?? null));
        $quote->setCustomerCompany(self::strOrNull($payload->customer['company'] ?? null));
        $quote->setCustomerAddress(self::strOrNull($payload->customer['address'] ?? null));

        $status = QuoteStatusTranslator::toCode($payload->status);
        if ('' === $status) {
            $status = Quote::STATUS_DRAFT;
        }
        $quote->setStatus($status);

        $quote->setGlobalDiscountCents($payload->discount->cents());
        $quote->setShippingCents($payload->shipping->cents());

        if (null !== $payload->conditions || !$clearItems) {
            $quote->setConditions(self::strOrNull($payload->conditions) ?? self::DEFAULT_CONDITIONS);
        }

        if (null !== $payload->validFrom) {
            $quote->setValidFrom(self::dateOrNull($payload->validFrom));
        } elseif (!$clearItems && null === $quote->getValidFrom()) {
            $quote->setValidFrom($this->today());
        }

        if (null !== $payload->validUntil) {
            $quote->setValidUntil(self::dateOrNull($payload->validUntil));
        } elseif (!$clearItems && null === $quote->getValidUntil()) {
            $baseDate = $quote->getValidFrom() ?? $this->today();
            $quote->setValidUntil($baseDate->modify('+30 days'));
        }

        if ($clearItems) {
            foreach ($quote->getItems() as $existing) {
                $quote->removeItem($existing);
                $this->persistence->removeItem($existing);
            }
        }

        foreach ($payload->items as $raw) {
            $item = $this->buildItemFromPayload($raw);
            $this->persistence->addItem($quote, $item);
        }
    }

    private function buildItemFromPayload(QuoteItemPayload $raw): QuoteItem
    {
        $name = $raw->name;
        $unitPriceCents = $raw->unitPriceCents;
        $unit = $raw->unit;
        $type = $raw->type ?? QuoteItem::TYPE_CUSTOM;

        if (null !== $raw->productId) {
            $product = $this->productRepository->find($raw->productId);
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
        if (null === $unitPriceCents) {
            $unitPriceCents = 0;
        }

        $item = new QuoteItem($name, $unitPriceCents);
        $item->setItemType($type);
        $item->setDescription(self::strOrNull($raw->description));
        $item->setUnit(self::strOrNull($unit));
        $item->setQuantity($raw->quantity);
        $item->setVatRateBps($raw->vatRateBps);
        $item->setDiscountCents($raw->discountCents);
        $item->setProductId($raw->productId);
        $item->setServiceId($raw->serviceId);

        return $item;
    }

    public function delete(Quote $quote): void
    {
        try {
            $this->persistence->delete($quote);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de supprimer le devis.', $exception);
        }
    }

    /** @return array{totalHt: int, totalVat: int, totalTtc: int} */
    public function computeTotals(Quote $quote): array
    {
        return $this->calculator->computeTotals($quote);
    }

    public static function strOrNull(mixed $v): ?string
    {
        if (null === $v) {
            return null;
        }
        $s = trim((string) $v);

        return '' === $s ? null : $s;
    }

    public static function dateOrNull(mixed $value): ?\DateTimeImmutable
    {
        $normalized = self::strOrNull($value);
        if (null === $normalized) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $normalized);
        if (false === $date) {
            throw new \InvalidArgumentException('Format de date invalide. Utilisez YYYY-MM-DD.');
        }

        return $date->setTime(0, 0);
    }

    private function today(): \DateTimeImmutable
    {
        return ($this->today ?? new \DateTimeImmutable('today'))->setTime(0, 0);
    }
}
