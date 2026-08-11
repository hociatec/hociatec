<?php

declare(strict_types=1);

namespace App\Module\User\Application\Provider;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\User\Application\Projection\UserProfileFormatter;
use App\Module\User\Domain\Entity\User;

final readonly class PersonalDataExportProvider
{
    public function __construct(
        private UserProfileFormatter $profiles,
        private OrderRepositoryPort $orders,
        private TradeInRequestRepositoryPort $tradeIns,
        private QuoteRepositoryPort $quotes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        return [
            'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'account' => $this->profiles->format($user) + [
                'communicationPreferences' => $user->getCommunicationPreferences(),
                'verified' => $user->isVerified(),
                'adminNotes' => $user->getAdminNotes(),
                'adminTags' => $user->getAdminTags(),
                'loyaltyPointsBalance' => $user->getLoyaltyPointsBalance(),
            ],
            'orders' => array_map(static fn ($order): array => [
                'id' => $order->getId(),
                'number' => $order->getNumber(),
                'status' => $order->getStatus(),
                'deliveryStatus' => $order->getDeliveryStatus(),
                'invoiceStatus' => $order->getInvoiceStatus(),
                'billingName' => $order->getBillingName(),
                'billingEmail' => $order->getBillingEmail(),
                'billingAddress' => $order->getBillingAddress(),
                'billingPostalCode' => $order->getBillingPostalCode(),
                'billingCity' => $order->getBillingCity(),
                'shippingName' => $order->getShippingName(),
                'shippingAddress' => $order->getShippingAddress(),
                'shippingPostalCode' => $order->getShippingPostalCode(),
                'shippingCity' => $order->getShippingCity(),
                'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $order->getUpdatedAt()->format(DATE_ATOM),
            ], $this->orders->findByUser($user, 1000)),
            'tradeIns' => array_map(static fn ($request): array => [
                'id' => $request->getId(),
                'reference' => $request->getReference(),
                'status' => $request->getStatus()->value,
                'firstName' => $request->getFirstName(),
                'lastName' => $request->getLastName(),
                'email' => $request->getEmail(),
                'phone' => $request->getPhone(),
                'productName' => $request->getProductName(),
                'brand' => $request->getBrand(),
                'model' => $request->getModel(),
                'serialNumber' => $request->getSerialNumber(),
                'description' => $request->getDescription(),
                'paymentStatus' => $request->getPaymentStatus(),
                'createdAt' => $request->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $request->getUpdatedAt()->format(DATE_ATOM),
            ], $this->tradeIns->findByUser($user, 1000)),
            'quotes' => array_map(static fn ($quote): array => [
                'id' => $quote->getId(),
                'number' => $quote->getNumber(),
                'status' => $quote->getStatus(),
                'customerName' => $quote->getCustomerName(),
                'customerEmail' => $quote->getCustomerEmail(),
                'customerCompany' => $quote->getCustomerCompany(),
                'customerAddress' => $quote->getCustomerAddress(),
                'createdAt' => $quote->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $quote->getUpdatedAt()->format(DATE_ATOM),
            ], $this->quotes->findByCustomerEmail($user->getEmail(), 1000)),
        ];
    }
}
