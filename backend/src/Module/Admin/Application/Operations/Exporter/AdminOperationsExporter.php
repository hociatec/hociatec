<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Exporter;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Port\RefundRequestRepositoryPort;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\User\Application\Port\UserRepositoryPort;

final readonly class AdminOperationsExporter
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private OrderRepositoryPort $orders,
        private UserRepositoryPort $users,
        private ProductCatalogRepository $products,
        private QuoteRepositoryPort $quotes,
        private RefundRequestRepositoryPort $refunds,
        private SupportRequestRepositoryPort $supportRequests,
    ) {
    }

    /** @return \Generator<list<int|string|null>> */
    public function rowsFor(string $resource): \Generator
    {
        yield from match ($resource) {
            'orders' => $this->orders(),
            'customers' => $this->customers(),
            'products' => $this->products(),
            'quotes' => $this->quotes(),
            'refunds' => $this->refunds(),
            'support' => $this->support(),
            default => $this->unknown(),
        };
    }

    /** @return \Generator<list<int|string|null>> */
    private function orders(): \Generator
    {
        yield ['id', 'numero', 'client', 'email', 'statut', 'total_centimes', 'date'];
        $offset = 0;
        do {
            $page = $this->orders->findBy([], ['createdAt' => 'DESC'], self::BATCH_SIZE, $offset);
            foreach ($page as $order) {
                yield [$order->getId(), $order->getNumber(), $order->getUser()->getFullName(), $order->getUser()->getEmail(), $order->getStatus(), $order->getTotalPriceCents(), $order->getCreatedAt()->format(DATE_ATOM)];
            }
            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));
    }

    /** @return \Generator<list<int|string|null>> */
    private function customers(): \Generator
    {
        yield ['id', 'nom', 'email', 'telephone', 'verifie', 'date_creation'];
        $offset = 0;
        do {
            $page = $this->users->findBy([], ['createdAt' => 'DESC'], self::BATCH_SIZE, $offset);
            foreach ($page as $user) {
                yield [$user->getId(), $user->getFullName(), $user->getEmail(), $user->getPhoneNumber(), $user->isVerified() ? 'oui' : 'non', $user->getCreatedAt()->format(DATE_ATOM)];
            }
            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));
    }

    /** @return \Generator<list<int|string|null>> */
    private function products(): \Generator
    {
        yield ['id', 'sku', 'nom', 'stock', 'prix_vente_centimes', 'prix_location_centimes', 'vente_active', 'location_active', 'publie'];
        $offset = 0;
        do {
            $page = $this->products->findBy([], ['updatedAt' => 'DESC'], self::BATCH_SIZE, $offset);
            foreach ($page as $product) {
                yield [
                    $product->getId(),
                    $product->getSku(),
                    $product->getName(),
                    $product->getStock(),
                    $product->getSalePriceCents(),
                    $product->getRentalPriceCents(),
                    $product->isAvailableForSale() ? 'oui' : 'non',
                    $product->isAvailableForRental() ? 'oui' : 'non',
                    $product->isPublished() ? 'oui' : 'non',
                ];
            }
            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));
    }

    /** @return \Generator<list<int|string|null>> */
    private function quotes(): \Generator
    {
        yield ['id', 'numero', 'client', 'email', 'statut', 'date'];
        $offset = 0;
        do {
            $page = $this->quotes->findBy([], ['createdAt' => 'DESC'], self::BATCH_SIZE, $offset);
            foreach ($page as $quote) {
                yield [$quote->getId(), $quote->getNumber(), $quote->getCustomerName(), $quote->getCustomerEmail(), $quote->getStatus(), $quote->getCreatedAt()->format(DATE_ATOM)];
            }
            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));
    }

    /** @return \Generator<list<int|string|null>> */
    private function refunds(): \Generator
    {
        yield ['id', 'commande', 'montant_centimes', 'statut', 'motif', 'stripe_refund_id', 'date'];
        $offset = 0;
        do {
            $page = $this->refunds->findBy([], ['createdAt' => 'DESC'], self::BATCH_SIZE, $offset);
            foreach ($page as $refund) {
                yield [$refund->getId(), $refund->getOrder()->getNumber(), $refund->getAmountCents(), $refund->getStatus(), $refund->getReason(), $refund->getStripeRefundId(), $refund->getCreatedAt()->format(DATE_ATOM)];
            }
            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));
    }

    /** @return \Generator<list<int|string|null>> */
    private function support(): \Generator
    {
        yield ['id', 'client', 'email', 'commande', 'statut', 'motif', 'sujet', 'date'];
        $offset = 0;
        do {
            $page = $this->supportRequests->findBy([], ['createdAt' => 'DESC'], self::BATCH_SIZE, $offset);
            foreach ($page as $support) {
                yield [$support->getId(), $support->getCustomer()->getFullName(), $support->getCustomer()->getEmail(), $support->getOrderNumber(), $support->getStatus(), $support->getReason(), $support->getSubject(), $support->getCreatedAt()->format(DATE_ATOM)];
            }
            $offset += self::BATCH_SIZE;
        } while (self::BATCH_SIZE === count($page));
    }

    /** @return \Generator<list<int|string|null>> */
    private function unknown(): \Generator
    {
        yield ['Erreur'];
        yield ['Export inconnu'];
    }
}
