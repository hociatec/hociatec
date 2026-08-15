<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\Workflow;

use App\Module\Order\Application\Factory\OrderNumberGenerator;
use App\Module\Order\Application\Workflow\StripeCheckoutService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rental\Application\Projection\RentalFormatter;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class RentalExtensionCheckoutService
{
    public function __construct(
        private OrderNumberGenerator $orderNumbers,
        private StripeCheckoutService $stripeCheckout,
        private RentalFormatter $formatter,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @return array{rental:array<string,mixed>,checkout:array<string,mixed>}
     */
    public function prepare(
        User $user,
        OrderItem $item,
        int $additionalMonths,
        \DateTimeImmutable $requestedDate,
        ?string $clientPlatform,
        \DateTimeImmutable $today,
    ): array {
        $order = $this->createExtensionOrder($user, $item, $additionalMonths);
        $item->requestRentalExtensionPayment($requestedDate, (int) $order->getId());

        $this->persistence->persist($item);
        $this->persistence->flush();

        $checkout = $this->stripeCheckout->createHostedCheckoutForOrder(
            $user,
            $order,
            $this->shippingAddressFromOrder($user, $order),
            $clientPlatform,
        );

        return [
            'rental' => $this->formatter->format($item, $today),
            'checkout' => [
                'mode' => 'redirect',
                'orderId' => $order->getId(),
                'checkoutUrl' => $checkout->getCheckoutUrl(),
                'checkoutSessionId' => $checkout->getStripeSessionId(),
            ],
        ];
    }

    private function createExtensionOrder(User $user, OrderItem $item, int $additionalMonths): Order
    {
        $originalOrder = $item->getOrder();
        if (!$originalOrder instanceof Order) {
            throw new \LogicException('La commande associée à cette location est introuvable.');
        }

        $amountCents = $item->getUnitPriceCents() * max(1, $item->getQuantity()) * $additionalMonths;
        $vatRate = max(0, $item->getVatRateBps());
        $subtotalHt = 0 === $vatRate
            ? $amountCents
            : (int) round($amountCents / (1 + ($vatRate / 10000)));
        $lineVat = max(0, $amountCents - $subtotalHt);

        $order = (new Order($this->orderNumbers->generate(), $user))
            ->setStatus(Order::STATUS_PENDING)
            ->setShippingName($originalOrder->getShippingName())
            ->setShippingAddress($originalOrder->getShippingAddress())
            ->setShippingPostalCode($originalOrder->getShippingPostalCode())
            ->setShippingCity($originalOrder->getShippingCity())
            ->setBillingName($originalOrder->getBillingName())
            ->setBillingCompany($originalOrder->getBillingCompany())
            ->setBillingCompanySiren($originalOrder->getBillingCompanySiren())
            ->setBillingCompanyVatNumber($originalOrder->getBillingCompanyVatNumber())
            ->setBillingEmail($originalOrder->getBillingEmail() ?: $user->getEmail())
            ->setBillingAddress($originalOrder->getBillingAddress())
            ->setBillingPostalCode($originalOrder->getBillingPostalCode())
            ->setBillingCity($originalOrder->getBillingCity())
            ->replacePaymentAmounts($subtotalHt, 0, $amountCents);

        $extensionLine = (new OrderItem(
            sprintf('Prolongation location - %s', $item->getProductName()),
            sprintf('%s-EXT', $item->getProductSku()),
            $amountCents,
            1,
        ))
            ->setSellingType('sale')
            ->setVatRateBps($item->getVatRateBps())
            ->replaceLineTotals($subtotalHt, $lineVat, $amountCents)
            ->setRentalOriginOrderItemId((int) $item->getId());

        $order->addItem($extensionLine);

        $this->persistence->persist($order);
        $this->persistence->persist($extensionLine);
        $this->persistence->flush();

        return $order;
    }

    private function shippingAddressFromOrder(User $user, Order $order): ShippingAddress
    {
        $name = trim((string) ($order->getShippingName() ?: $order->getBillingName() ?: $user->getFirstName().' '.$user->getLastName()));
        $address = trim((string) ($order->getShippingAddress() ?: $order->getBillingAddress()));
        $postalCode = trim((string) ($order->getShippingPostalCode() ?: $order->getBillingPostalCode()));
        $city = trim((string) ($order->getShippingCity() ?: $order->getBillingCity()));

        if ('' === $name || '' === $address || '' === $postalCode || '' === $city) {
            throw new \InvalidArgumentException('Une adresse de livraison valide est requise pour régler la prolongation.');
        }

        return new ShippingAddress($user, $name, $address, $postalCode, $city);
    }
}
