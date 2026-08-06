<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;

final readonly class QuoteToOrderConverter
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private UserRepositoryPort $users,
        private ProductCatalogRepository $products,
        private QuoteCalculator $quoteCalculator,
        private QuoteToOrderServices $orderServices,
        private OperationsPersistence $persistence,
        private OrderFormatter $orderFormatter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function convert(string $reference): array
    {
        $quote = $this->findQuote(trim($reference));
        $this->assertConvertible($quote);
        $customer = $this->resolveCustomer($quote);
        $order = $this->createOrder($quote, $customer);

        $this->persistence->persist($order);
        $this->persistence->commit();
        if (null === $order->getId()) {
            throw new \RuntimeException('La commande n\'a pas d\'identifiant après enregistrement.');
        }

        $quote->convertToOrder($order->getId(), $order->getNumber());
        $this->persistence->commit();

        [$emailSent, $emailError] = $this->sendNotification($order);

        return [
            'order' => $this->orderFormatter->formatOrder($order),
            'emailNotificationSent' => $emailSent,
            'emailNotificationError' => $emailError,
        ];
    }

    private function findQuote(string $reference): Quote
    {
        $quote = ctype_digit($reference)
            ? $this->quotes->find((int) $reference)
            : $this->quotes->findOneBy(['number' => $reference]);

        if (!$quote instanceof Quote) {
            throw new OperationsResourceNotFoundException('Devis introuvable.');
        }

        return $quote;
    }

    private function assertConvertible(Quote $quote): void
    {
        if (null !== $quote->getConvertedOrderId()) {
            throw new \InvalidArgumentException(sprintf('Ce devis a déjà été converti en commande %s.', (string) $quote->getConvertedOrderNumber()));
        }
        if (Quote::STATUS_ACCEPTED !== $quote->getStatus()) {
            throw new \InvalidArgumentException('Le devis doit être accepté avant conversion en commande.');
        }
        if ($quote->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Le devis ne contient aucune ligne à convertir.');
        }
    }

    private function resolveCustomer(Quote $quote): User
    {
        $email = trim((string) $quote->getCustomerEmail());
        if ('' === $email) {
            throw new \InvalidArgumentException('Le devis doit avoir un email client pour être converti.');
        }

        $customer = $this->users->findOneByEmailInsensitive($email);
        if (!$customer instanceof User) {
            throw new \InvalidArgumentException('Aucun compte client ne correspond à l’email du devis.');
        }

        return $customer;
    }

    private function createOrder(Quote $quote, User $customer): Order
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

    /**
     * @return array{bool, ?string}
     */
    private function sendNotification(Order $order): array
    {
        try {
            return [$this->orderServices->notifications->sendOrderCreatedIfNeeded($order), null];
        } catch (\RuntimeException $exception) {
            $this->orderServices->events->log($order, null, 'email_failed', 'Échec email commande à régler: '.$exception->getMessage());

            return [false, 'La notification email n’a pas pu être envoyée.'];
        }
    }
}
