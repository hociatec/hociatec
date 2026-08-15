<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Exception\CheckoutRequestException;
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

    public function create(User $user, Order $order, ShippingAddress $address, ?string $clientPlatform = null): OrderCheckoutSession
    {
        $orderId = $order->getId();
        if (null === $orderId) {
            throw CheckoutRequestException::invalidOrder();
        }

        $existing = $this->checkoutSessions->findReusableOpenSessionForOrder($user, $orderId);
        if (null !== $existing && (null === $existing->getExpiresAt() || $existing->getExpiresAt() > new \DateTimeImmutable())) {
            $this->redirectUrls->assertTrusted($existing->getCheckoutUrl());

            return $existing;
        }

        $items = $this->payloads->orderItems($order);
        $localToken = bin2hex(random_bytes(16));
        $customerName = trim($user->getFirstName().' '.$user->getLastName());
        $metadata = [
            'local_checkout_token' => $localToken,
            'order_id' => (string) $orderId,
            'user_id' => (string) ($user->getId() ?? 0),
        ];
        [$successUrl, $cancelUrl] = $this->checkoutReturnUrls($order, $clientPlatform);
        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
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
        ];
        if ('ios' === $clientPlatform) {
            $payload['origin_context'] = 'mobile_app';
        }

        $session = $this->stripe->createCheckoutSession(
            $payload,
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
            ->setBillingCompany($order->getBillingCompany() ?: ($address->isProfessional() ? $address->getCompany() : null))
            ->setBillingCompanySiren($order->getBillingCompanySiren() ?: ($address->isProfessional() ? $address->getCompanySiren() : null))
            ->setBillingCompanyVatNumber($order->getBillingCompanyVatNumber() ?: ($address->isProfessional() ? $address->getCompanyVatNumber() : null))
            ->setPurchaseOrderNumber($order->getPurchaseOrderNumber())
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

    /**
     * @return array{0: string, 1: string}
     */
    private function checkoutReturnUrls(Order $order, ?string $clientPlatform): array
    {
        $orderId = (int) $order->getId();
        if ('ios' === $clientPlatform) {
            $frontendUrl = rtrim($this->frontendUrl, '/');

            return [
                $frontendUrl.'/checkout/app-return?status=success&session_id={CHECKOUT_SESSION_ID}',
                $frontendUrl.'/checkout/app-return?status=cancelled',
            ];
        }

        $frontendUrl = rtrim($this->frontendUrl, '/');
        if ($this->isRentalExtensionOrder($order)) {
            return [
                $frontendUrl.'/locations/me?payment=success&session_id={CHECKOUT_SESSION_ID}',
                $frontendUrl.'/locations/me?payment=cancelled',
            ];
        }

        return [
            $frontendUrl.'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            $frontendUrl.'/orders/'.$orderId.'?payment=cancelled',
        ];
    }

    private function isRentalExtensionOrder(Order $order): bool
    {
        if ($order->getItems()->isEmpty()) {
            return false;
        }

        foreach ($order->getItems() as $item) {
            if (null === $item->getRentalOriginOrderItemId()) {
                return false;
            }
        }

        return true;
    }
}
