<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Entity;

use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class OrderCheckoutSessionTest extends TestCase
{
    public function testItExposesAndUpdatesCheckoutSessionState(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $session = new OrderCheckoutSession('tok', $user, 'carttok', 12, 'stripe_1', 'https://checkout.example.com');

        $createdAt = $session->getCreatedAt();

        self::assertNull($session->getId());
        self::assertSame('tok', $session->getToken());
        self::assertSame($user, $session->getUser());
        self::assertSame('carttok', $session->getCartToken());
        self::assertSame(12, $session->getShippingAddressId());
        self::assertSame('stripe_1', $session->getStripeSessionId());
        self::assertSame('https://checkout.example.com', $session->getCheckoutUrl());
        self::assertSame(OrderCheckoutSession::STATUS_OPEN, $session->getStatus());
        self::assertSame('ada@example.com', $session->getCustomerEmail());
        self::assertTrue($session->isPendingFulfillment());

        $expiresAt = new \DateTimeImmutable('+1 day');
        $itemsPayload = [['sku' => 'A1']];

        $session
            ->setCartId(99)
            ->setStripePaymentIntentId('pi_1')
            ->setStripePaymentStatus('requires_payment_method')
            ->setLastStripeEventType('checkout.session.completed')
            ->setFailureCode('card_error')
            ->setFailureMessage('Declined')
            ->setCustomerFullName('Ada Lovelace')
            ->setCustomerEmail('billing@example.com')
            ->setShippingName('Ada')
            ->setShippingAddress('1 rue')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setBillingName('Ada')
            ->setBillingCompany('OpenAI')
            ->setBillingCompanySiren('123')
            ->setBillingCompanyVatNumber('FR123')
            ->setPurchaseOrderNumber('PO-1')
            ->setBillingEmail('facture@example.com')
            ->setBillingAddress('2 rue')
            ->setBillingPostalCode('69000')
            ->setBillingCity('Lyon')
            ->setStatus(OrderCheckoutSession::STATUS_FAILED)
            ->setCurrencyCode('USD')
            ->setSubtotalPriceCents(10000)
            ->setDiscountAmountCents(500)
            ->setTotalPriceCents(9500)
            ->setAppliedPromotionName('Promo')
            ->setAppliedPromotionSlug('promo')
            ->setItemsPayload($itemsPayload)
            ->setOrderId(42)
            ->setExpiresAt($expiresAt);

        self::assertSame(99, $session->getCartId());
        self::assertSame('pi_1', $session->getStripePaymentIntentId());
        self::assertSame('requires_payment_method', $session->getStripePaymentStatus());
        self::assertSame('checkout.session.completed', $session->getLastStripeEventType());
        self::assertSame('card_error', $session->getFailureCode());
        self::assertSame('Declined', $session->getFailureMessage());
        self::assertSame('Ada Lovelace', $session->getCustomerFullName());
        self::assertSame('billing@example.com', $session->getCustomerEmail());
        self::assertSame('Ada', $session->getShippingName());
        self::assertSame('1 rue', $session->getShippingAddress());
        self::assertSame('75001', $session->getShippingPostalCode());
        self::assertSame('Paris', $session->getShippingCity());
        self::assertSame('Ada', $session->getBillingName());
        self::assertSame('OpenAI', $session->getBillingCompany());
        self::assertSame('123', $session->getBillingCompanySiren());
        self::assertSame('FR123', $session->getBillingCompanyVatNumber());
        self::assertSame('PO-1', $session->getPurchaseOrderNumber());
        self::assertSame('facture@example.com', $session->getBillingEmail());
        self::assertSame('2 rue', $session->getBillingAddress());
        self::assertSame('69000', $session->getBillingPostalCode());
        self::assertSame('Lyon', $session->getBillingCity());
        self::assertSame(OrderCheckoutSession::STATUS_FAILED, $session->getStatus());
        self::assertSame('USD', $session->getCurrencyCode());
        self::assertSame(10000, $session->getSubtotalPriceCents());
        self::assertSame(500, $session->getDiscountAmountCents());
        self::assertSame(9500, $session->getTotalPriceCents());
        self::assertSame('Promo', $session->getAppliedPromotionName());
        self::assertSame('promo', $session->getAppliedPromotionSlug());
        self::assertSame($itemsPayload, $session->getItemsPayload());
        self::assertSame(42, $session->getOrderId());
        self::assertSame($expiresAt, $session->getExpiresAt());

        $session->markFailed('pi_2', 'failed', 'payment_intent.payment_failed', 'do_not_honor', 'Declined again');
        self::assertSame(OrderCheckoutSession::STATUS_FAILED, $session->getStatus());
        self::assertSame('pi_2', $session->getStripePaymentIntentId());
        self::assertSame('failed', $session->getStripePaymentStatus());
        self::assertSame('payment_intent.payment_failed', $session->getLastStripeEventType());
        self::assertSame('do_not_honor', $session->getFailureCode());
        self::assertSame('Declined again', $session->getFailureMessage());

        $session->setOrderId(null);
        $session->markPaid('pi_paid', 'paid', 'checkout.session.completed');
        self::assertSame(OrderCheckoutSession::STATUS_PAID, $session->getStatus());
        self::assertSame('pi_paid', $session->getStripePaymentIntentId());
        self::assertSame('paid', $session->getStripePaymentStatus());
        self::assertSame('checkout.session.completed', $session->getLastStripeEventType());
        self::assertNull($session->getFailureCode());
        self::assertNull($session->getFailureMessage());
        self::assertInstanceOf(\DateTimeImmutable::class, $session->getCompletedAt());
        self::assertTrue($session->isPendingFulfillment());

        $session->setOrderId(50);
        self::assertFalse($session->isPendingFulfillment());

        $session->markExpired('checkout.session.expired');
        self::assertSame(OrderCheckoutSession::STATUS_EXPIRED, $session->getStatus());
        self::assertSame('checkout.session.expired', $session->getLastStripeEventType());

        usleep(1000);
        $session->touch();
        self::assertGreaterThanOrEqual($createdAt, $session->getCreatedAt());
        self::assertTrue(true);
    }
}
