<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartItem;
use App\Module\Cart\Entity\CartSession;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Entity\OrderItem;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Promotion\Service\PromotionEngine;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\Voucher\Service\VoucherEngine;
use Doctrine\ORM\EntityManagerInterface;

final class StripeCheckoutService
{
    public function __construct(
        private readonly StripeApiClient $stripe,
        private readonly OrderCheckoutSessionRepository $checkoutSessions,
        private readonly PromotionEngine $promotionEngine,
        private readonly VoucherEngine $voucherEngine,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function createHostedCheckout(User $user, CartSession $cart, ShippingAddress $address): OrderCheckoutSession
    {
        $existing = $this->checkoutSessions->findReusableOpenSessionForCart($user, $cart->getToken());
        if ($existing !== null && ($existing->getExpiresAt() === null || $existing->getExpiresAt() > new \DateTimeImmutable())) {
            return $existing;
        }

        $summary = $this->buildSummary($cart, $user);
        $customerFullName = trim($user->getFirstName() . ' ' . $user->getLastName());
        $payload = $this->buildItemsPayload($cart);
        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $localToken = bin2hex(random_bytes(16));

        $sessionData = $this->stripe->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl . '/panier?payment=cancelled',
            'customer_email' => $user->getEmail(),
            'client_reference_id' => $localToken,
            'locale' => 'fr',
            'payment_method_types' => ['card'],
            'metadata' => [
                'local_checkout_token' => $localToken,
                'cart_token' => $cart->getToken(),
                'user_id' => (string) ($user->getId() ?? 0),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'local_checkout_token' => $localToken,
                    'cart_token' => $cart->getToken(),
                    'user_id' => (string) ($user->getId() ?? 0),
                ],
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Commande Hociatec',
                        'description' => sprintf('%d article(s)', count($payload)),
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
            (string) $sessionData['id'],
            (string) $sessionData['url'],
        );

        $checkout
            ->setCartId($cart->getId())
            ->setCurrencyCode('EUR')
            ->setSubtotalPriceCents((int) $summary['subtotalPriceCents'])
            ->setDiscountAmountCents((int) $summary['discountAmountCents'])
            ->setTotalPriceCents((int) $summary['totalPriceCents'])
            ->setAppliedPromotionName($summary['appliedVoucher']['name'] ?? ($summary['appliedPromotion']['name'] ?? null))
            ->setAppliedPromotionSlug($summary['appliedVoucher']['code'] ?? ($summary['appliedPromotion']['slug'] ?? null))
            ->setCustomerFullName($customerFullName !== '' ? $customerFullName : $address->getName())
            ->setCustomerEmail($user->getEmail())
            ->setShippingName($customerFullName !== '' ? $customerFullName : $address->getName())
            ->setShippingAddress($address->getAddress())
            ->setShippingPostalCode($address->getPostalCode())
            ->setShippingCity($address->getCity())
            ->setBillingName($customerFullName !== '' ? $customerFullName : $address->getName())
            ->setBillingCompany($address->getCompany())
            ->setBillingCompanySiren($address->getCompanySiren())
            ->setBillingCompanyVatNumber($address->getCompanyVatNumber())
            ->setPurchaseOrderNumber($address->getPurchaseOrderNumber())
            ->setBillingEmail($user->getEmail())
            ->setBillingAddress($address->getAddress())
            ->setBillingPostalCode($address->getPostalCode())
            ->setBillingCity($address->getCity())
            ->setItemsPayload($payload)
            ->setExpiresAt(isset($sessionData['expires_at']) ? new \DateTimeImmutable('@' . (int) $sessionData['expires_at']) : null);

        $this->em->persist($checkout);
        $this->em->flush();

        return $checkout;
    }

    public function createHostedCheckoutForOrder(User $user, Order $order, ShippingAddress $address): OrderCheckoutSession
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            throw new \InvalidArgumentException('Commande invalide.');
        }

        $existing = $this->checkoutSessions->findReusableOpenSessionForOrder($user, $orderId);
        if ($existing !== null && ($existing->getExpiresAt() === null || $existing->getExpiresAt() > new \DateTimeImmutable())) {
            return $existing;
        }

        $payload = $this->buildItemsPayloadFromOrder($order);
        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $localToken = bin2hex(random_bytes(16));
        $customerFullName = trim($user->getFirstName() . ' ' . $user->getLastName());

        $sessionData = $this->stripe->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $frontendUrl . '/orders/' . $orderId . '?payment=cancelled',
            'customer_email' => $user->getEmail(),
            'client_reference_id' => $localToken,
            'locale' => 'fr',
            'payment_method_types' => ['card'],
            'metadata' => [
                'local_checkout_token' => $localToken,
                'order_id' => (string) $orderId,
                'user_id' => (string) ($user->getId() ?? 0),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'local_checkout_token' => $localToken,
                    'order_id' => (string) $orderId,
                    'user_id' => (string) ($user->getId() ?? 0),
                ],
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Commande ' . $order->getNumber(),
                        'description' => sprintf('%d ligne(s)', count($payload)),
                    ],
                    'unit_amount' => $order->getTotalPriceCents(),
                ],
                'quantity' => 1,
            ]],
        ]);

        $checkout = new OrderCheckoutSession(
            $localToken,
            $user,
            'order-' . $orderId,
            (int) $address->getId(),
            (string) $sessionData['id'],
            (string) $sessionData['url'],
        );

        $checkout
            ->setOrderId($orderId)
            ->setCurrencyCode($order->getCurrencyCode())
            ->setSubtotalPriceCents($order->getSubtotalPriceCents())
            ->setDiscountAmountCents($order->getDiscountAmountCents())
            ->setTotalPriceCents($order->getTotalPriceCents())
            ->setAppliedPromotionName($order->getAppliedPromotionName())
            ->setAppliedPromotionSlug($order->getAppliedPromotionSlug())
            ->setCustomerFullName($customerFullName !== '' ? $customerFullName : $address->getName())
            ->setCustomerEmail($user->getEmail())
            ->setShippingName($order->getShippingName() ?: ($customerFullName !== '' ? $customerFullName : $address->getName()))
            ->setShippingAddress($order->getShippingAddress() ?: $address->getAddress())
            ->setShippingPostalCode($order->getShippingPostalCode() ?: $address->getPostalCode())
            ->setShippingCity($order->getShippingCity() ?: $address->getCity())
            ->setBillingName($order->getBillingName() ?: ($customerFullName !== '' ? $customerFullName : $address->getName()))
            ->setBillingCompany($order->getBillingCompany() ?: $address->getCompany())
            ->setBillingCompanySiren($order->getBillingCompanySiren() ?: $address->getCompanySiren())
            ->setBillingCompanyVatNumber($order->getBillingCompanyVatNumber() ?: $address->getCompanyVatNumber())
            ->setPurchaseOrderNumber($order->getPurchaseOrderNumber() ?: $address->getPurchaseOrderNumber())
            ->setBillingEmail($order->getBillingEmail() ?: $user->getEmail())
            ->setBillingAddress($order->getBillingAddress() ?: $address->getAddress())
            ->setBillingPostalCode($order->getBillingPostalCode() ?: $address->getPostalCode())
            ->setBillingCity($order->getBillingCity() ?: $address->getCity())
            ->setItemsPayload($payload)
            ->setExpiresAt(isset($sessionData['expires_at']) ? new \DateTimeImmutable('@' . (int) $sessionData['expires_at']) : null);

        $this->em->persist($checkout);
        $this->em->flush();

        return $checkout;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItemsPayload(CartSession $cart): array
    {
        $items = [];

        /** @var CartItem $item */
        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();
            $rentalMonths = $item->getRentalMonths();
            $effectiveUnitPrice = $product->getPriceCents();
            $label = $product->getName();
            if ($product->getSellingType() === 'rental' && $rentalMonths !== null) {
                $effectiveUnitPrice *= max(1, $rentalMonths);
                $label .= sprintf(' (%d mois)', $rentalMonths);
            }

            $items[] = [
                'productId' => $product->getId(),
                'productName' => $label,
                'productSku' => $product->getSku(),
                'unitPriceCents' => $effectiveUnitPrice,
                'quantity' => $item->getQuantity(),
                'vatRateBps' => 2000,
                'rentalMonths' => $rentalMonths,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItemsPayloadFromOrder(Order $order): array
    {
        $items = [];

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $items[] = [
                'productId' => $product?->getId(),
                'productName' => $item->getProductName(),
                'productSku' => $item->getProductSku(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'quantity' => $item->getQuantity(),
                'vatRateBps' => $item->getVatRateBps(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(CartSession $cart, User $user): array
    {
        $promotionSummary = $this->promotionEngine->calculateCartSummary($cart, $user);
        $voucherSummary = $this->voucherEngine->calculateCartSummary($cart, $user, $cart->getVoucherCode());

        return ($cart->getVoucherCode() !== null && ($voucherSummary['voucherCodeStatus'] ?? 'none') === 'applied')
            ? $voucherSummary
            : $promotionSummary;
    }
}
