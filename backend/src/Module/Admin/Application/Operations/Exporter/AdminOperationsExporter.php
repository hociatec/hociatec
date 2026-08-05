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
    public function __construct(
        private OrderRepositoryPort $orders,
        private UserRepositoryPort $users,
        private ProductCatalogRepository $products,
        private QuoteRepositoryPort $quotes,
        private RefundRequestRepositoryPort $refunds,
        private SupportRequestRepositoryPort $supportRequests,
    ) {
    }

    /** @return list<list<int|string|null>> */
    public function rowsFor(string $resource): array
    {
        return match ($resource) {
            'orders' => $this->orders(),
            'customers' => $this->customers(),
            'products' => $this->products(),
            'quotes' => $this->quotes(),
            'refunds' => $this->refunds(),
            'support' => $this->support(),
            default => [['Erreur'], ['Export inconnu']],
        };
    }

    /** @return list<list<int|string|null>> */
    private function orders(): array
    {
        $rows = [['id', 'numero', 'client', 'email', 'statut', 'total_centimes', 'date']];
        foreach ($this->orders->findBy([], ['createdAt' => 'DESC']) as $order) {
            $rows[] = [$order->getId(), $order->getNumber(), $order->getUser()->getFullName(), $order->getUser()->getEmail(), $order->getStatus(), $order->getTotalPriceCents(), $order->getCreatedAt()->format(DATE_ATOM)];
        }

        return $rows;
    }

    /** @return list<list<int|string|null>> */
    private function customers(): array
    {
        $rows = [['id', 'nom', 'email', 'telephone', 'verifie', 'date_creation']];
        foreach ($this->users->findBy([], ['createdAt' => 'DESC']) as $user) {
            $rows[] = [$user->getId(), $user->getFullName(), $user->getEmail(), $user->getPhoneNumber(), $user->isVerified() ? 'oui' : 'non', $user->getCreatedAt()->format(DATE_ATOM)];
        }

        return $rows;
    }

    /** @return list<list<int|string|null>> */
    private function products(): array
    {
        $rows = [['id', 'sku', 'nom', 'stock', 'prix_centimes', 'publie']];
        foreach ($this->products->findBy([], ['updatedAt' => 'DESC']) as $product) {
            $rows[] = [$product->getId(), $product->getSku(), $product->getName(), $product->getStock(), $product->getPriceCents(), $product->isPublished() ? 'oui' : 'non'];
        }

        return $rows;
    }

    /** @return list<list<int|string|null>> */
    private function quotes(): array
    {
        $rows = [['id', 'numero', 'client', 'email', 'statut', 'date']];
        foreach ($this->quotes->findBy([], ['createdAt' => 'DESC']) as $quote) {
            $rows[] = [$quote->getId(), $quote->getNumber(), $quote->getCustomerName(), $quote->getCustomerEmail(), $quote->getStatus(), $quote->getCreatedAt()->format(DATE_ATOM)];
        }

        return $rows;
    }

    /** @return list<list<int|string|null>> */
    private function refunds(): array
    {
        $rows = [['id', 'commande', 'montant_centimes', 'statut', 'motif', 'stripe_refund_id', 'date']];
        foreach ($this->refunds->findBy([], ['createdAt' => 'DESC']) as $refund) {
            $rows[] = [$refund->getId(), $refund->getOrder()->getNumber(), $refund->getAmountCents(), $refund->getStatus(), $refund->getReason(), $refund->getStripeRefundId(), $refund->getCreatedAt()->format(DATE_ATOM)];
        }

        return $rows;
    }

    /** @return list<list<int|string|null>> */
    private function support(): array
    {
        $rows = [['id', 'client', 'email', 'commande', 'statut', 'motif', 'sujet', 'date']];
        foreach ($this->supportRequests->findBy([], ['createdAt' => 'DESC']) as $support) {
            $rows[] = [$support->getId(), $support->getCustomer()->getFullName(), $support->getCustomer()->getEmail(), $support->getOrder()?->getNumber(), $support->getStatus(), $support->getReason(), $support->getSubject(), $support->getCreatedAt()->format(DATE_ATOM)];
        }

        return $rows;
    }
}
