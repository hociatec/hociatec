<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class CartOrderCreator
{
    public function __construct(
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private OrderCreationServices $orderCreation,
        private CartSessionRepositoryPort $carts,
        private CartOrderLineConverter $lineConverter,
        private CartOrderSummaryBuilder $summaryBuilder,
    ) {
    }

    public function create(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        if (0 === $cart->getItems()->count()) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        $summary = $this->summaryBuilder->build($cart, $user);

        return $this->transactions->transactional(
            function () use ($user, $cart, $address, $summary): Order {
                $cartId = $cart->getId();
                if (null === $cartId) {
                    throw new \InvalidArgumentException('Panier invalide.');
                }

                $lockedCart = $this->carts->findForUpdate($cartId);
                if (null === $lockedCart) {
                    throw new \InvalidArgumentException('Panier introuvable.');
                }
                if ($lockedCart->isConverted()) {
                    throw new \InvalidArgumentException('Ce panier a deja ete valide.');
                }
                if (0 === $lockedCart->getItems()->count()) {
                    throw new \InvalidArgumentException('Le panier est vide.');
                }

                $order = $this->createOrder($user, $address, $summary);
                $this->lineConverter->addLines($order, $lockedCart);

                $this->orderCreation->invoiceCalculator->snapshot($order);
                $this->persistence->persist($order);
                $this->persistence->commit();

                if (null === $order->getId()) {
                    throw new \InvalidArgumentException('Commande invalide.');
                }
                $lockedCart->markConverted($order->getId());
                $this->persistence->persist($lockedCart);
                $this->persistence->commit();

                return $order;
            },
        );
    }

    /** @param array<string, mixed> $summary */
    private function createOrder(User $user, ShippingAddress $address, array $summary): Order
    {
        $customerName = trim($user->getFirstName().' '.$user->getLastName());

        return (new Order($this->orderCreation->orderNumbers->generate(), $user))
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setShippingName('' !== $customerName ? $customerName : $address->getName())
            ->setShippingAddress($address->getAddress())
            ->setShippingPostalCode($address->getPostalCode())
            ->setShippingCity($address->getCity())
            ->setBillingName('' !== $customerName ? $customerName : $address->getName())
            ->setBillingCompany($address->getCompany())
            ->setBillingCompanySiren($address->getCompanySiren())
            ->setBillingCompanyVatNumber($address->getCompanyVatNumber())
            ->setPurchaseOrderNumber($address->getPurchaseOrderNumber())
            ->setBillingAddress($address->getAddress())
            ->setBillingPostalCode($address->getPostalCode())
            ->setBillingCity($address->getCity())
            ->setBillingEmail($user->getEmail())
            ->setInvoiceNumber($this->orderCreation->invoiceNumbers->generate())
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setCurrencyCode('EUR')
            ->setElectronicFormat('UBL-2.1')
            ->replacePaymentAmounts((int) $summary['subtotalPriceCents'], (int) $summary['discountAmountCents'], (int) $summary['totalPriceCents'])
            ->setAppliedPromotionName($summary['appliedVoucher']['name'] ?? ($summary['appliedPromotion']['name'] ?? null))
            ->setAppliedPromotionSlug($summary['appliedVoucher']['code'] ?? ($summary['appliedPromotion']['slug'] ?? null));
    }
}
