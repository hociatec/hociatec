<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Application\Factory\InvoiceNumberGenerator;
use App\Module\Order\Application\Factory\OrderNumberGenerator;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Workflow\OrderEventLogger;
use App\Module\Order\Application\Workflow\OrderNotificationEmailService;
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
        private OrderNumberGenerator $orderNumbers,
        private InvoiceNumberGenerator $invoiceNumbers,
        private OrderInvoiceCalculator $invoiceCalculator,
        private OrderNotificationEmailService $notifications,
        private OrderEventLogger $events,
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
        $quote->setConvertedOrder($order);
        $quote->setStatus(Quote::STATUS_ACCEPTED);
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
        if ($quote->getConvertedOrder() instanceof Order) {
            throw new \InvalidArgumentException(sprintf('Ce devis a déjà été converti en commande %s.', $quote->getConvertedOrder()->getNumber()));
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
        $order = new Order($this->orderNumbers->generate(), $customer);
        $order
            ->setStatus(Order::STATUS_PENDING)
            ->setInvoiceNumber($this->invoiceNumbers->generate())
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setBillingName($quote->getCustomerName())
            ->setBillingCompany($quote->getCustomerCompany())
            ->setBillingEmail($quote->getCustomerEmail())
            ->setBillingAddress($quote->getCustomerAddress())
            ->setSubtotalPriceCents((int) $totals['totalHt'])
            ->setDiscountAmountCents($quote->getGlobalDiscountCents())
            ->setTotalPriceCents((int) $totals['totalTtc']);

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

        $this->invoiceCalculator->snapshot($order);

        return $order;
    }

    /**
     * @return array{bool, ?string}
     */
    private function sendNotification(Order $order): array
    {
        try {
            return [$this->notifications->sendOrderCreatedIfNeeded($order), null];
        } catch (\RuntimeException $exception) {
            $this->events->log($order, null, 'email_failed', 'Échec email commande à régler: '.$exception->getMessage());

            return [false, 'La notification email n’a pas pu être envoyée.'];
        }
    }
}
