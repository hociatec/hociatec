<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\User\Domain\Entity\User;

final readonly class QuoteOrderFactory
{
    public function __construct(
        private ProductCatalogRepository $products,
        private QuoteCalculator $quoteCalculator,
        private QuoteToOrderServices $orderServices,
        private OperationsPersistence $persistence,
    ) {
    }

    public function create(Quote $quote, User $customer): Order
    {
        $totals = $this->quoteCalculator->computeTotals($quote);
        $order = new Order($this->orderServices->orderNumbers->generate(), $customer);
        $order
            ->setStatus(Order::STATUS_PENDING)
            ->setInvoiceNumber($this->orderServices->invoiceNumbers->generate())
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setBillingName($quote->getCustomerName())
            ->setBillingCompany($quote->getCustomerCompany())
            ->setBillingEmail($quote->getCustomerEmail())
            ->setBillingAddress($quote->getCustomerAddress())
            ->replacePaymentAmounts((int) $totals['totalHt'], $quote->getGlobalDiscountCents(), (int) $totals['totalTtc']);

        foreach ($quote->getItems() as $quoteItem) {
            $product = null !== $quoteItem->getProductId() ? $this->products->findProduct($quoteItem->getProductId()) : null;
            $item = new OrderItem(
                $quoteItem->getName(),
                $product instanceof Product ? $product->getSku() : 'DEVIS-'.$quote->getNumber(),
                $quoteItem->getUnitPriceCents(),
                $quoteItem->getQuantity(),
            );
            $item
                ->setProduct($product instanceof Product ? $product : null)
                ->setVatRateBps($quoteItem->getVatRateBps());
            $order->addItem($item);
            $this->persistence->persist($item);
        }

        $this->orderServices->invoiceCalculator->snapshot($order);

        return $order;
    }
}
