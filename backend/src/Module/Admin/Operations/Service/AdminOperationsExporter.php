<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Repository\RefundRequestRepository;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Support\Repository\SupportRequestRepository;
use App\Module\User\Repository\UserRepository;

final readonly class AdminOperationsExporter
{
    public function __construct(
        private OrderRepository $orders,
        private UserRepository $users,
        private ProductRepository $products,
        private QuoteRepository $quotes,
        private RefundRequestRepository $refunds,
        private SupportRequestRepository $supportRequests,
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
