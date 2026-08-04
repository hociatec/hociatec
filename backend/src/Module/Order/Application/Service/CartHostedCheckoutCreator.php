<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;

final readonly class CartHostedCheckoutCreator
{
    public function __construct(
        private StripeApiClient $stripe,
        private OrderCheckoutSessionRepository $checkoutSessions,
        private StripeCheckoutPayloadProvider $payloads,
        private DoctrineUnitOfWork $persistence,
        private string $frontendUrl,
    ) {
    }

    public function create(User $user, CartSession $cart, ShippingAddress $address): OrderCheckoutSession
    {
        $existing = $this->checkoutSessions->findReusableOpenSessionForCart($user, $cart->getToken());
        if (null !== $existing && (null === $existing->getExpiresAt() || $existing->getExpiresAt() > new \DateTimeImmutable())) {
            return $existing;
        }

        $summary = $this->payloads->cartSummary($cart, $user);
        $items = $this->payloads->cartItems($cart);
        $customerName = trim($user->getFirstName().' '.$user->getLastName());
        $localToken = bin2hex(random_bytes(16));
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $metadata = [
            'local_checkout_token' => $localToken,
            'cart_token' => $cart->getToken(),
            'user_id' => (string) ($user->getId() ?? 0),
        ];
        $session = $this->stripe->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $frontendUrl.'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl.'/panier?payment=cancelled',
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
                        'name' => 'Commande Hociatec',
                        'description' => sprintf('%d article(s)', count($items)),
                    ],
                    'unit_amount' => (int) $summary['totalPriceCents'],
                ],
                'quantity' => 1,
            ]],
        ]);

        $checkout = new OrderCheckoutSession(
            $localToken,
            $user,
            $cart->getToken(),
            (int) $address->getId(),
            (string) $session['id'],
            (string) $session['url'],
        );
        $checkout
            ->setCartId($cart->getId())
            ->setCurrencyCode('EUR')
            ->setSubtotalPriceCents((int) $summary['subtotalPriceCents'])
            ->setDiscountAmountCents((int) $summary['discountAmountCents'])
            ->setTotalPriceCents((int) $summary['totalPriceCents'])
            ->setAppliedPromotionName($summary['appliedVoucher']['name'] ?? ($summary['appliedPromotion']['name'] ?? null))
            ->setAppliedPromotionSlug($summary['appliedVoucher']['code'] ?? ($summary['appliedPromotion']['slug'] ?? null))
            ->setCustomerFullName('' !== $customerName ? $customerName : $address->getName())
            ->setCustomerEmail($user->getEmail())
            ->setShippingName('' !== $customerName ? $customerName : $address->getName())
            ->setShippingAddress($address->getAddress())
            ->setShippingPostalCode($address->getPostalCode())
            ->setShippingCity($address->getCity())
            ->setBillingName('' !== $customerName ? $customerName : $address->getName())
            ->setBillingCompany($address->getCompany())
            ->setBillingCompanySiren($address->getCompanySiren())
            ->setBillingCompanyVatNumber($address->getCompanyVatNumber())
            ->setPurchaseOrderNumber($address->getPurchaseOrderNumber())
            ->setBillingEmail($user->getEmail())
            ->setBillingAddress($address->getAddress())
            ->setBillingPostalCode($address->getPostalCode())
            ->setBillingCity($address->getCity())
            ->setItemsPayload($items)
            ->setExpiresAt(isset($session['expires_at']) ? new \DateTimeImmutable('@'.(int) $session['expires_at']) : null);

        $this->persistence->persist($checkout);
        $this->persistence->flush();

        return $checkout;
    }
}
