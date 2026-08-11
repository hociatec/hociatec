<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Port\OrderCheckoutSessionRepositoryPort;
use App\Module\Order\Application\Provider\StripeCheckoutPayloadProvider;
use App\Module\Order\Application\Security\CheckoutRedirectUrlValidator;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class OrderHostedCheckoutCreator
{
    public function __construct(
        private StripeApiClient $stripe,
        private OrderCheckoutSessionRepositoryPort $checkoutSessions,
        private StripeCheckoutPayloadProvider $payloads,
        private UnitOfWork $persistence,
        private CheckoutRedirectUrlValidator $redirectUrls,
        private string $frontendUrl,
    ) {
    }

    public function create(User $user, Order $order, ShippingAddress $address): OrderCheckoutSession
    {
        $orderId = $order->getId();
        if (null === $orderId) {
            throw new \InvalidArgumentException('Commande invalide.');
        }

        $existing = $this->checkoutSessions->findReusableOpenSessionForOrder($user, $orderId);
        if (null !== $existing && (null === $existing->getExpiresAt() || $existing->getExpiresAt() > new \DateTimeImmutable())) {
            $this->redirectUrls->assertTrusted($existing->getCheckoutUrl());

            return $existing;
        }

        $items = $this->payloads->orderItems($order);
        $localToken = bin2hex(random_bytes(16));
        $customerName = trim($user->getFirstName().' '.$user->getLastName());
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $metadata = [
            'local_checkout_token' => $localToken,
            'order_id' => (string) $orderId,
            'user_id' => (string) ($user->getId() ?? 0),
        ];
        $session = $this->stripe->createCheckoutSession(
            [
                'mode' => 'payment',
                'success_url' => $frontendUrl.'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $frontendUrl.'/orders/'.$orderId.'?payment=cancelled',
                'customer_email' => $user->getEmail(),
                'client_reference_id' => $localToken,
                'locale' => 'fr',
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
                'payment_intent_data' => ['metadata' => $metadata],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Commande '.$order->getNumber(),
                            'description' => sprintf('%d ligne(s)', count($items)),
                        ],
                        'unit_amount' => $order->getTotalPriceCents(),
                    ],
                    'quantity' => 1,
                ]],
            ],
            'order_checkout:'.hash('sha256', implode('|', [
                (string) $orderId,
                $order->getNumber(),
                (string) $order->getTotalPriceCents(),
            ])),
        );
        $checkoutUrl = (string) ($session['url'] ?? '');
        $this->redirectUrls->assertTrusted($checkoutUrl);

        $checkout = new OrderCheckoutSession(
            $localToken,
            $user,
            'order-'.$orderId,
            (int) $address->getId(),
            (string) $session['id'],
            $checkoutUrl,
        );
        $checkout
            ->setOrderId($orderId)
            ->replacePricing($order->getSubtotalPriceCents(), $order->getDiscountAmountCents(), $order->getTotalPriceCents(), $order->getCurrencyCode())
            ->applyPromotion(
                $order->getAppliedPromotionName(),
                $order->getAppliedPromotionSlug(),
                $order->getDiscountAmountCents(),
                $order->getTotalPriceCents(),
            )
            ->setCustomerFullName('' !== $customerName ? $customerName : $address->getName())
            ->setCustomerEmail($user->getEmail())
            ->setShippingName($order->getShippingName() ?: ('' !== $customerName ? $customerName : $address->getName()))
            ->setShippingAddress($order->getShippingAddress() ?: $address->getAddress())
            ->setShippingPostalCode($order->getShippingPostalCode() ?: $address->getPostalCode())
            ->setShippingCity($order->getShippingCity() ?: $address->getCity())
            ->setBillingName($order->getBillingName() ?: ('' !== $customerName ? $customerName : $address->getName()))
            ->setBillingCompany($order->getBillingCompany() ?: $address->getCompany())
            ->setBillingCompanySiren($order->getBillingCompanySiren() ?: $address->getCompanySiren())
            ->setBillingCompanyVatNumber($order->getBillingCompanyVatNumber() ?: $address->getCompanyVatNumber())
            ->setPurchaseOrderNumber($order->getPurchaseOrderNumber() ?: $address->getPurchaseOrderNumber())
            ->setBillingEmail($order->getBillingEmail() ?: $user->getEmail())
            ->setBillingAddress($order->getBillingAddress() ?: $address->getAddress())
            ->setBillingPostalCode($order->getBillingPostalCode() ?: $address->getPostalCode())
            ->setBillingCity($order->getBillingCity() ?: $address->getCity())
            ->setItemsPayload($items)
            ->setExpiresAt(isset($session['expires_at']) ? new \DateTimeImmutable('@'.(int) $session['expires_at']) : null);

        $this->persistence->persist($checkout);
        $this->persistence->flush();

        return $checkout;
    }
}
