<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Workflow;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Quote\Application\Factory\QuoteItemFactory;
use App\Module\Quote\Application\Factory\QuoteNumberGenerator;
use App\Module\Quote\Application\Mapper\QuoteHydrator;
use App\Module\Quote\Application\Mapper\QuoteValueNormalizer;
use App\Module\Quote\Application\Port\QuotePersistencePort;
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
        private readonly QuotePersistencePort $persistence,
        ProductCatalogRepository $productRepository,
        private readonly QuoteNumberGenerator $numberGenerator,
        private readonly QuoteCalculator $calculator,
        ?\DateTimeImmutable $today = null,
        ?QuoteHydrator $hydrator = null,
    ) {
        $this->hydrator = $hydrator ?? new QuoteHydrator($this->persistence, new QuoteItemFactory($productRepository), $today);
    }

    private readonly QuoteHydrator $hydrator;

    public function createEmpty(): Quote
    {
        $quote = new Quote($this->numberGenerator->generate());
        try {
            $this->persistence->save($quote);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de créer le devis.', $exception);
        }

        return $quote;
    }

    public function createFromPayload(QuotePayload $payload): Quote
    {
        $quote = new Quote($this->numberGenerator->generate());
        $this->hydrator->hydrate($quote, $payload);
        try {
            $this->persistence->save($quote);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw QuoteOperationException::failed('Impossible de créer le devis.', $exception);
        }

        return $quote;
    }

    public function updateFromPayload(Quote $quote, QuotePayload $payload): Quote
    {
        $this->hydrator->hydrate($quote, $payload, true);
        try {
            $this->persistence->commit();
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

    public function delete(Quote $quote): void
    {
        try {
            $this->persistence->delete($quote);
            $this->persistence->commit();
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
        return QuoteValueNormalizer::strOrNull($v);
    }

    public static function dateOrNull(mixed $value): ?\DateTimeImmutable
    {
        return QuoteValueNormalizer::dateOrNull($value);
    }
}
