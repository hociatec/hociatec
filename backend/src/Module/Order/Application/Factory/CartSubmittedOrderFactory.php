<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Factory;

use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Application\DTO\OrderCreationData;
use App\Module\Order\Domain\Entity\Order;

final readonly class CartSubmittedOrderFactory
{
    public function __construct(
        private OrderNumberGenerator $orderNumbers,
        private InvoiceNumberGenerator $invoiceNumbers,
        private OrderInvoiceCalculator $invoiceCalculator,
    ) {
    }

    public function create(OrderCreationData $data): Order
    {
        $address = $data->address;
        $summary = $data->summary;
        $user = $data->user;
        $customerName = trim($user->getFirstName().' '.$user->getLastName());
        $displayName = '' !== $customerName ? $customerName : $address->getName();

        $order = (new Order($this->orderNumbers->generate(), $user))
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setShippingName($displayName)
            ->setShippingAddress($address->getAddress())
            ->setShippingPostalCode($address->getPostalCode())
            ->setShippingCity($address->getCity())
            ->setBillingName($displayName)
            ->setBillingCompany($address->isProfessional() ? $address->getCompany() : null)
            ->setBillingCompanySiren($address->isProfessional() ? $address->getCompanySiren() : null)
            ->setBillingCompanyVatNumber($address->isProfessional() ? $address->getCompanyVatNumber() : null)
            ->setPurchaseOrderNumber(null)
            ->setBillingAddress($address->getAddress())
            ->setBillingPostalCode($address->getPostalCode())
            ->setBillingCity($address->getCity())
            ->setBillingEmail($user->getEmail())
            ->setInvoiceNumber($this->invoiceNumbers->generate())
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt($data->invoicedAt)
            ->setCurrencyCode('EUR')
            ->setElectronicFormat('UBL-2.1')
            ->replacePaymentAmounts($summary->subtotalPriceCents, $summary->discountAmountCents, $summary->totalPriceCents)
            ->setAppliedPromotionName($summary->appliedPromotionName)
            ->setAppliedPromotionSlug($summary->appliedPromotionSlug);

        $this->invoiceCalculator->snapshot($order);

        return $order;
    }
}
