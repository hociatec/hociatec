<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;

final class AdminPaymentFormatterTest extends MiscSupportTestCase
{
    public function testAdminPaymentFormatterSummariesExposeAttentionState(): void
    {
        $payment = new OrderCheckoutSession('tok', $this->user(), 'cart', 15, 'sess_1', 'https://stripe.test');
        $this->setId($payment, 22);
        $payment
            ->setStatus(OrderCheckoutSession::STATUS_FAILED)
            ->setStripePaymentIntentId('pi_1')
            ->setStripePaymentStatus('requires_action')
            ->setLastStripeEventType('payment_intent.payment_failed')
            ->setFailureCode('card_declined')
            ->setFailureMessage('Declined')
            ->setCustomerFullName('Ada Lovelace')
            ->setTotalPriceCents(12345)
            ->setSubtotalPriceCents(14000)
            ->setDiscountAmountCents(1655)
            ->setItemsPayload([['sku' => 'IP-1']]);

        $formatter = new AdminPaymentFormatter();
        $summary = $formatter->summary($payment);
        self::assertSame('Échoué', $summary['statusLabel']);
        self::assertSame('Action requise', $summary['stripePaymentStatusLabel']);
        self::assertSame('Paiement refusé', $summary['lastStripeEventLabel']);
        self::assertSame('Carte refusée', $summary['failureCodeLabel']);
        self::assertTrue($summary['requiresAttention']);

        $detail = $formatter->detail($payment);
        self::assertSame([['sku' => 'IP-1']], $detail['items']);
        self::assertSame(14000, $detail['subtotalPriceCents']);
        self::assertSame(1655, $detail['discountAmountCents']);
        self::assertSame('Ouverte', $formatter->stripeCheckoutStatusLabel('open'));
        self::assertNull($formatter->stripeCheckoutStatusLabel(''));
        self::assertSame('Terminée', $formatter->stripeCheckoutStatusLabel('complete'));
        self::assertSame('Expirée', $formatter->stripeCheckoutStatusLabel('expired'));
        self::assertSame('unknown', $formatter->stripeCheckoutStatusLabel('unknown'));
        self::assertSame('Ouvert', $formatter->statusLabel(OrderCheckoutSession::STATUS_OPEN));
        self::assertSame('Payé', $formatter->statusLabel(OrderCheckoutSession::STATUS_PAID));
        self::assertSame('Expiré', $formatter->statusLabel(OrderCheckoutSession::STATUS_EXPIRED));
        self::assertSame('custom', $formatter->statusLabel('custom'));
        self::assertSame('Non payé', $formatter->stripePaymentStatusLabel('unpaid'));
        self::assertSame('Aucun paiement requis', $formatter->stripePaymentStatusLabel('no_payment_required'));
        self::assertSame('Moyen de paiement requis', $formatter->stripePaymentStatusLabel('requires_payment_method'));
        self::assertSame('Confirmation requise', $formatter->stripePaymentStatusLabel('requires_confirmation'));
        self::assertSame('En cours de traitement', $formatter->stripePaymentStatusLabel('processing'));
        self::assertSame('Réussi', $formatter->stripePaymentStatusLabel('succeeded'));
        self::assertSame('Annulé', $formatter->stripePaymentStatusLabel('canceled'));
        self::assertSame('other', $formatter->stripePaymentStatusLabel('other'));
        self::assertSame('Session de paiement finalisée', $formatter->stripeEventLabel('checkout.session.completed'));
        self::assertSame('Paiement asynchrone confirmé', $formatter->stripeEventLabel('checkout.session.async_payment_succeeded'));
        self::assertSame('Paiement asynchrone échoué', $formatter->stripeEventLabel('checkout.session.async_payment_failed'));
        self::assertSame('Session de paiement expirée', $formatter->stripeEventLabel('checkout.session.expired'));
        self::assertSame('event.unknown', $formatter->stripeEventLabel('event.unknown'));
        self::assertSame('Fonds insuffisants', $formatter->failureCodeLabel('insufficient_funds'));
        self::assertSame('Carte expirée', $formatter->failureCodeLabel('expired_card'));
        self::assertSame('Code CVC incorrect', $formatter->failureCodeLabel('incorrect_cvc'));
        self::assertSame('Numéro de carte incorrect', $formatter->failureCodeLabel('incorrect_number'));
        self::assertSame('Code postal incorrect', $formatter->failureCodeLabel('incorrect_zip'));
        self::assertSame('Code CVC invalide', $formatter->failureCodeLabel('invalid_cvc'));
        self::assertSame('Mois d’expiration invalide', $formatter->failureCodeLabel('invalid_expiry_month'));
        self::assertSame('Année d’expiration invalide', $formatter->failureCodeLabel('invalid_expiry_year'));
        self::assertSame('Carte déclarée perdue', $formatter->failureCodeLabel('lost_card'));
        self::assertSame('Carte déclarée volée', $formatter->failureCodeLabel('stolen_card'));
        self::assertSame('Erreur de traitement bancaire', $formatter->failureCodeLabel('processing_error'));
        self::assertSame('Authentification bancaire requise', $formatter->failureCodeLabel('authentication_required'));
        self::assertSame('other_code', $formatter->failureCodeLabel('other_code'));
        self::assertSame([
            ['value' => OrderCheckoutSession::STATUS_OPEN, 'label' => 'Ouvert'],
            ['value' => OrderCheckoutSession::STATUS_PAID, 'label' => 'Payé'],
            ['value' => OrderCheckoutSession::STATUS_FAILED, 'label' => 'Échoué'],
            ['value' => OrderCheckoutSession::STATUS_EXPIRED, 'label' => 'Expiré'],
        ], $formatter->statusOptions());
    }
}
