<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Controller;

use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Entity\StockMovement;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Catalog\Repository\StockMovementRepository;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderItem;
use App\Module\Order\Entity\RefundRequest;
use App\Module\Order\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Repository\OrderEventRepository;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Order\Repository\RefundRequestRepository;
use App\Module\Order\Service\InvoiceNumberGenerator;
use App\Module\Order\Service\OrderEventLogger;
use App\Module\Order\Service\OrderFormatter;
use App\Module\Order\Service\OrderInvoiceCalculator;
use App\Module\Order\Service\OrderNotificationEmailService;
use App\Module\Order\Service\OrderNumberGenerator;
use App\Module\Order\Service\StripeApiClient;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\QuoteItem;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Support\Entity\SupportRequest;
use App\Module\Support\Repository\SupportRequestRepository;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\AdminCustomerEmailService;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations')]
#[IsGranted('ROLE_ADMIN')]
final class AdminOperationsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupportRequestRepository $supportRequests,
        private readonly RefundRequestRepository $refunds,
        private readonly StockMovementRepository $stockMovements,
        private readonly ProductRepository $products,
        private readonly OrderRepository $orders,
        private readonly OrderEventRepository $orderEvents,
        private readonly OrderCheckoutSessionRepository $payments,
        private readonly UserRepository $users,
        private readonly QuoteRepository $quotes,
        private readonly OrderNumberGenerator $orderNumberGenerator,
        private readonly InvoiceNumberGenerator $invoiceNumberGenerator,
        private readonly OrderInvoiceCalculator $invoiceCalculator,
        private readonly OrderNotificationEmailService $orderNotificationEmails,
        private readonly QuoteCalculator $quoteCalculator,
        private readonly StripeApiClient $stripe,
        private readonly AdminCustomerEmailService $customerEmails,
        private readonly OrderEventLogger $orderEventLogger,
    ) {
    }

    #[Route('/overview', name: 'api_admin_operations_overview', methods: ['GET'])]
    public function overview(): JsonResponse
    {
        $lowStockThreshold = 3;

        return ApiResponse::success([
            'support' => [
                'openCount' => $this->supportRequests->count(['status' => [
                    SupportRequest::STATUS_NEW,
                    SupportRequest::STATUS_IN_PROGRESS,
                    SupportRequest::STATUS_WAITING_CUSTOMER,
                ]]),
                'items' => array_map([$this, 'formatSupportRequest'], $this->supportRequests->findBy([], ['updatedAt' => 'DESC'], 8)),
            ],
            'refunds' => [
                'pendingCount' => $this->refunds->count(['status' => RefundRequest::STATUS_REQUESTED]),
                'items' => array_map([$this, 'formatRefund'], $this->refunds->findBy([], ['updatedAt' => 'DESC'], 8)),
            ],
            'stock' => [
                'lowStockThreshold' => $lowStockThreshold,
                'lowStockCount' => $this->products->countLowStock($lowStockThreshold),
                'lowStockItems' => array_map([$this, 'formatLowStockProduct'], $this->products->findLowStock($lowStockThreshold, 8)),
                'movements' => array_map([$this, 'formatStockMovement'], $this->stockMovements->findBy([], ['createdAt' => 'DESC'], 8)),
            ],
            'emails' => [
                'items' => array_slice($this->buildEmailLogs(), 0, 8),
            ],
            'actions' => [
                ['label' => 'Exporter les commandes', 'href' => '/api/admin/operations/exports/orders.csv'],
                ['label' => 'Exporter les clients', 'href' => '/api/admin/operations/exports/customers.csv'],
                ['label' => 'Exporter les produits', 'href' => '/api/admin/operations/exports/products.csv'],
                ['label' => 'Exporter les devis', 'href' => '/api/admin/operations/exports/quotes.csv'],
            ],
        ]);
    }

    #[Route('/support-requests', name: 'api_admin_operations_support_list', methods: ['GET'])]
    public function listSupportRequests(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map([$this, 'formatSupportRequest'], $this->supportRequests->findBy([], ['updatedAt' => 'DESC'])),
        ]);
    }

    #[Route('/support-requests', name: 'api_admin_operations_support_create', methods: ['POST'])]
    public function createSupportRequest(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $customer = $this->users->find((int) ($payload['customerId'] ?? 0));
        if (!$customer instanceof User) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        $support = new SupportRequest($customer, (string) ($payload['subject'] ?? 'Demande SAV'));
        $support
            ->setReason((string) ($payload['reason'] ?? 'other'))
            ->setMessage(isset($payload['message']) ? (string) $payload['message'] : null)
            ->setInternalNotes(isset($payload['internalNotes']) ? (string) $payload['internalNotes'] : null);

        $orderId = (int) ($payload['orderId'] ?? 0);
        if ($orderId > 0) {
            $order = $this->orders->find($orderId);
            if ($order instanceof Order) {
                $support->setOrder($order);
            }
        }

        $this->em->persist($support);
        $this->em->flush();

        return ApiResponse::created(['item' => $this->formatSupportRequest($support)]);
    }

    #[Route('/support-requests/{id}', name: 'api_admin_operations_support_update', methods: ['PATCH'])]
    public function updateSupportRequest(int $id, Request $request): JsonResponse
    {
        $support = $this->supportRequests->find($id);
        if (!$support instanceof SupportRequest) {
            return ApiResponse::error('Demande SAV introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $this->jsonPayload($request);
        if (isset($payload['status'])) {
            $support->setStatus((string) $payload['status']);
        }
        if (array_key_exists('internalNotes', $payload)) {
            $support->setInternalNotes($payload['internalNotes'] !== null ? (string) $payload['internalNotes'] : null);
        }
        if (isset($payload['subject'])) {
            $support->setSubject((string) $payload['subject']);
        }

        $this->em->flush();

        return ApiResponse::success(['item' => $this->formatSupportRequest($support)]);
    }

    #[Route('/support-requests/{id}/reply', name: 'api_admin_operations_support_reply', methods: ['POST'])]
    public function replySupportRequest(int $id, Request $request): JsonResponse
    {
        $support = $this->supportRequests->find($id);
        if (!$support instanceof SupportRequest) {
            return ApiResponse::error('Demande SAV introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $this->jsonPayload($request);
        $subject = trim((string) ($payload['subject'] ?? ('Réponse à votre demande SAV #' . $support->getId())));
        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return ApiResponse::error('Le message de réponse est obligatoire.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->customerEmails->send($support->getCustomer(), $subject, $message);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $note = trim(sprintf(
            "%s\n[%s] Réponse envoyée au client : %s",
            (string) $support->getInternalNotes(),
            (new \DateTimeImmutable())->format('d/m/Y H:i'),
            $subject,
        ));
        $support
            ->setInternalNotes($note)
            ->setStatus((string) ($payload['status'] ?? SupportRequest::STATUS_WAITING_CUSTOMER));

        $this->em->flush();

        return ApiResponse::success(['sent' => true, 'item' => $this->formatSupportRequest($support)]);
    }

    #[Route('/refunds', name: 'api_admin_operations_refunds_list', methods: ['GET'])]
    public function listRefunds(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map([$this, 'formatRefund'], $this->refunds->findBy([], ['updatedAt' => 'DESC'])),
        ]);
    }

    #[Route('/refunds', name: 'api_admin_operations_refunds_create', methods: ['POST'])]
    public function createRefund(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $order = $this->orders->find((int) ($payload['orderId'] ?? 0));
        if (!$order instanceof Order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $refund = new RefundRequest($order, (int) ($payload['amountCents'] ?? $order->getTotalPriceCents()), $this->currentAdmin());
        $refund
            ->setReason(isset($payload['reason']) ? (string) $payload['reason'] : null)
            ->setInternalNotes(isset($payload['internalNotes']) ? (string) $payload['internalNotes'] : null)
            ->setPaymentId(isset($payload['paymentId']) ? (int) $payload['paymentId'] : null)
            ->setCurrencyCode((string) ($payload['currencyCode'] ?? $order->getCurrencyCode()));

        $this->em->persist($refund);
        $this->em->flush();

        return ApiResponse::created(['item' => $this->formatRefund($refund)]);
    }

    #[Route('/refunds/{id}', name: 'api_admin_operations_refunds_update', methods: ['PATCH'])]
    public function updateRefund(int $id, Request $request): JsonResponse
    {
        $refund = $this->refunds->find($id);
        if (!$refund instanceof RefundRequest) {
            return ApiResponse::error('Remboursement introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $this->jsonPayload($request);
        if (isset($payload['status'])) {
            $refund->setStatus((string) $payload['status']);
        }
        if (array_key_exists('stripeRefundId', $payload)) {
            $refund->setStripeRefundId($payload['stripeRefundId'] !== null ? (string) $payload['stripeRefundId'] : null);
        }
        if (array_key_exists('internalNotes', $payload)) {
            $refund->setInternalNotes($payload['internalNotes'] !== null ? (string) $payload['internalNotes'] : null);
        }

        $this->em->flush();

        return ApiResponse::success(['item' => $this->formatRefund($refund)]);
    }

    #[Route('/refunds/{id}/process-stripe', name: 'api_admin_operations_refunds_process_stripe', methods: ['POST'])]
    public function processStripeRefund(int $id, Request $request): JsonResponse
    {
        $refund = $this->refunds->find($id);
        if (!$refund instanceof RefundRequest) {
            return ApiResponse::error('Remboursement introuvable.', Response::HTTP_NOT_FOUND);
        }
        if ($refund->getStripeRefundId() !== null || $refund->getStatus() === RefundRequest::STATUS_PROCESSED) {
            return ApiResponse::error('Ce remboursement a déjà été traité.');
        }
        if ($refund->getAmountCents() <= 0) {
            return ApiResponse::error('Le montant du remboursement doit être supérieur à zéro.', Response::HTTP_BAD_REQUEST);
        }

        $payload = $this->jsonPayload($request);
        $confirmation = trim((string) ($payload['confirmation'] ?? ''));
        if ($confirmation !== 'REMBOURSER') {
            return ApiResponse::error('Confirmation requise : saisis REMBOURSER pour déclencher Stripe.', Response::HTTP_BAD_REQUEST);
        }

        $paymentIntentId = is_string($payload['paymentIntentId'] ?? null) && trim((string) $payload['paymentIntentId']) !== ''
            ? trim((string) $payload['paymentIntentId'])
            : $this->findPaymentIntentForOrder($refund->getOrder());
        if ($paymentIntentId === null) {
            return ApiResponse::error('Aucun PaymentIntent Stripe trouvé pour cette commande.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $stripeRefund = $this->stripe->createRefund([
                'payment_intent' => $paymentIntentId,
                'amount' => $refund->getAmountCents(),
                'reason' => 'requested_by_customer',
                'metadata[refund_request_id]' => (string) $refund->getId(),
                'metadata[order_number]' => $refund->getOrder()->getNumber(),
            ]);
        } catch (\Throwable $exception) {
            return ApiResponse::error('Stripe a refusé le remboursement : ' . $exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $stripeRefundId = is_string($stripeRefund['id'] ?? null) ? $stripeRefund['id'] : null;
        $refund
            ->setStripeRefundId($stripeRefundId)
            ->setStatus(RefundRequest::STATUS_PROCESSED);
        $this->em->flush();

        $this->orderEventLogger->log(
            $refund->getOrder(),
            $this->currentAdmin(),
            'refund_processed',
            sprintf('Remboursement Stripe %s créé pour %s.', $stripeRefundId ?? '-', $refund->getAmountCents() / 100),
        );

        return ApiResponse::success(['item' => $this->formatRefund($refund), 'stripeRefund' => $stripeRefund]);
    }

    #[Route('/stock-movements', name: 'api_admin_operations_stock_list', methods: ['GET'])]
    public function listStockMovements(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map([$this, 'formatStockMovement'], $this->stockMovements->findBy([], ['createdAt' => 'DESC'], 100)),
        ]);
    }

    #[Route('/stock-movements', name: 'api_admin_operations_stock_create', methods: ['POST'])]
    public function createStockMovement(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $product = $this->products->find((int) ($payload['productId'] ?? 0));
        if (!$product instanceof Product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $delta = (int) ($payload['delta'] ?? 0);
        if ($delta === 0) {
            return ApiResponse::error('Le mouvement de stock doit être différent de zéro.');
        }

        $before = $product->getStock();
        $after = max(0, $before + $delta);
        $product->setStock($after);
        $movement = new StockMovement($product, $after - $before, $before, $after, (string) ($payload['reason'] ?? 'adjustment'), $this->currentAdmin());
        $movement->setNote(isset($payload['note']) ? (string) $payload['note'] : null);

        $this->em->persist($movement);
        $this->em->flush();

        return ApiResponse::created(['item' => $this->formatStockMovement($movement)]);
    }

    #[Route('/products/{id}/low-stock-threshold', name: 'api_admin_operations_product_low_stock_threshold', methods: ['PATCH'])]
    public function updateLowStockThreshold(int $id, Request $request): JsonResponse
    {
        $product = $this->products->find($id);
        if (!$product instanceof Product) {
            return ApiResponse::error('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $this->jsonPayload($request);
        $threshold = (int) ($payload['threshold'] ?? -1);
        if ($threshold < 0) {
            return ApiResponse::error('Le seuil doit être un entier positif.', Response::HTTP_BAD_REQUEST);
        }

        $product->setLowStockThreshold($threshold);
        $this->em->flush();

        return ApiResponse::success(['product' => $this->formatLowStockProduct($product)]);
    }

    #[Route('/fulfillment/orders', name: 'api_admin_operations_fulfillment_orders', methods: ['GET'])]
    public function fulfillmentOrders(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map([$this, 'formatFulfillmentOrder'], $this->orders->findFulfillmentQueue(50)),
        ]);
    }

    #[Route('/fulfillment/orders/{id}/ship', name: 'api_admin_operations_fulfillment_ship', methods: ['PATCH'])]
    public function shipFulfillmentOrder(int $id, Request $request): JsonResponse
    {
        $order = $this->orders->find($id);
        if (!$order instanceof Order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $this->jsonPayload($request);
        $carrier = $this->normalizeNullableString($payload['carrier'] ?? $order->getDeliveryCarrier());
        $trackingNumber = $this->normalizeNullableString($payload['trackingNumber'] ?? $order->getDeliveryTrackingNumber());
        $trackingUrl = $this->normalizeNullableString($payload['trackingUrl'] ?? $order->getDeliveryTrackingUrl());
        if ($trackingUrl !== null && filter_var($trackingUrl, FILTER_VALIDATE_URL) === false) {
            return ApiResponse::error('Lien de suivi invalide.', Response::HTTP_BAD_REQUEST);
        }

        $order
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_SHIPPED)
            ->setDeliveryCarrier($carrier)
            ->setDeliveryTrackingNumber($trackingNumber)
            ->setDeliveryTrackingUrl($trackingUrl);
        if ($order->getDeliveryShippedAt() === null) {
            $order->setDeliveryShippedAt(new \DateTimeImmutable());
        }
        $this->em->flush();

        $this->orderEventLogger->log(
            $order,
            $this->currentAdmin(),
            'order_shipped',
            sprintf('Commande marquée expédiée%s.', $trackingNumber !== null ? ' avec suivi ' . $trackingNumber : ''),
        );

        return ApiResponse::success(['order' => $this->formatFulfillmentOrder($order)]);
    }

    #[Route('/email-logs', name: 'api_admin_operations_email_logs', methods: ['GET'])]
    public function emailLogs(): JsonResponse
    {
        return ApiResponse::success(['items' => $this->buildEmailLogs()]);
    }

    #[Route('/customers/{id}/timeline', name: 'api_admin_operations_customer_timeline', methods: ['GET'])]
    public function customerTimeline(int $id): JsonResponse
    {
        $customer = $this->users->find($id);
        if (!$customer instanceof User) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        $items = [];
        foreach ($this->orders->findBy(['user' => $customer], ['createdAt' => 'DESC']) as $order) {
            $items[] = [
                'type' => 'order',
                'label' => 'Commande ' . $order->getNumber(),
                'description' => OrderFormatter::formatStatusLabel($order->getStatus()) . ' · ' . $order->getTotalPriceCents() / 100 . ' €',
                'date' => $order->getCreatedAt()->format(DATE_ATOM),
                'href' => '/admin/orders/' . $order->getId(),
            ];
        }
        foreach ($this->supportRequests->findBy(['customer' => $customer], ['updatedAt' => 'DESC']) as $support) {
            $items[] = [
                'type' => 'support',
                'label' => 'SAV #' . $support->getId() . ' · ' . $support->getSubject(),
                'description' => $this->supportStatusLabel($support->getStatus()),
                'date' => $support->getCreatedAt()->format(DATE_ATOM),
                'href' => '/admin/operations',
            ];
        }
        foreach ($this->quotes->findBy(['customerEmail' => $customer->getEmail()], ['createdAt' => 'DESC']) as $quote) {
            $items[] = [
                'type' => 'quote',
                'label' => 'Devis ' . $quote->getNumber(),
                'description' => $quote->getStatus(),
                'date' => $quote->getCreatedAt()->format(DATE_ATOM),
                'href' => '/admin/quotes/' . $quote->getId(),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $b['date'], (string) $a['date']));

        return ApiResponse::success(['items' => $items]);
    }

    #[Route('/orders/bulk-status', name: 'api_admin_operations_orders_bulk_status', methods: ['POST'])]
    public function bulkOrderStatus(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $ids = array_values(array_filter(array_map('intval', (array) ($payload['orderIds'] ?? []))));
        $status = (string) ($payload['status'] ?? '');
        if ($ids === [] || !in_array($status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
            return ApiResponse::error('Sélection ou statut invalide.');
        }

        $updated = 0;
        foreach ($ids as $id) {
            $order = $this->orders->find($id);
            if ($order instanceof Order) {
                $order->setStatus($status);
                $updated++;
            }
        }
        $this->em->flush();

        return ApiResponse::success(['updated' => $updated]);
    }

    #[Route('/quotes/{reference}/convert-to-order', name: 'api_admin_operations_quote_convert', methods: ['POST'])]
    public function convertQuoteToOrder(string $reference): JsonResponse
    {
        $reference = trim($reference);
        $quote = ctype_digit($reference)
            ? $this->quotes->find((int) $reference)
            : $this->quotes->findOneBy(['number' => $reference]);

        if (!$quote instanceof Quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }
        if ($quote->getConvertedOrder() instanceof Order) {
            return ApiResponse::error(sprintf(
                'Ce devis a déjà été converti en commande %s.',
                $quote->getConvertedOrder()->getNumber(),
            ));
        }
        if ($quote->getStatus() !== Quote::STATUS_ACCEPTED) {
            return ApiResponse::error('Le devis doit être accepté avant conversion en commande.');
        }
        if ($quote->getItems()->isEmpty()) {
            return ApiResponse::error('Le devis ne contient aucune ligne à convertir.');
        }

        $email = $quote->getCustomerEmail();
        if ($email === null || trim($email) === '') {
            return ApiResponse::error('Le devis doit avoir un email client pour être converti.');
        }
        $customer = $this->users->findOneByEmailInsensitive($email);
        if (!$customer instanceof User) {
            return ApiResponse::error('Aucun compte client ne correspond à l’email du devis.');
        }

        $totals = $this->quoteCalculator->computeTotals($quote);
        $order = new Order($this->orderNumberGenerator->generate(), $customer);
        $order
            ->setStatus(Order::STATUS_PENDING)
            ->setInvoiceNumber($this->invoiceNumberGenerator->generate())
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
            $product = $quoteItem->getProductId() !== null ? $this->products->find($quoteItem->getProductId()) : null;
            $item = new OrderItem(
                $quoteItem->getName(),
                $product instanceof Product ? $product->getSku() : 'DEVIS-' . $quote->getNumber(),
                $quoteItem->getUnitPriceCents(),
                $quoteItem->getQuantity(),
            );
            $item
                ->setProduct($product instanceof Product ? $product : null)
                ->setVatRateBps($quoteItem->getVatRateBps());
            $order->addItem($item);
            $this->em->persist($item);
        }

        $this->invoiceCalculator->snapshot($order);
        $this->em->persist($order);
        $quote->setConvertedOrder($order);
        $quote->setStatus(Quote::STATUS_ACCEPTED);
        $this->em->flush();

        $emailNotificationSent = false;
        $emailNotificationError = null;
        try {
            $emailNotificationSent = $this->orderNotificationEmails->sendOrderCreatedIfNeeded($order);
        } catch (\Throwable $exception) {
            $emailNotificationError = $exception->getMessage();
            $this->orderEventLogger->log($order, null, 'email_failed', 'Échec email commande à régler: ' . $exception->getMessage());
        }

        return ApiResponse::created([
            'order' => OrderFormatter::formatOrder($order),
            'emailNotificationSent' => $emailNotificationSent,
            'emailNotificationError' => $emailNotificationError,
        ]);
    }

    #[Route('/exports/{resource}.csv', name: 'api_admin_operations_exports', methods: ['GET'])]
    public function exportCsv(string $resource): StreamedResponse
    {
        $rows = match ($resource) {
            'orders' => $this->exportOrders(),
            'customers' => $this->exportCustomers(),
            'products' => $this->exportProducts(),
            'quotes' => $this->exportQuotes(),
            'refunds' => $this->exportRefunds(),
            'support' => $this->exportSupport(),
            default => [['Erreur'], ['Export inconnu']],
        };

        $response = new StreamedResponse(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $resource . '.csv"');

        return $response;
    }

    private function jsonPayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        return is_array($payload) ? $payload : [];
    }

    private function currentAdmin(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    private function formatSupportRequest(SupportRequest $support): array
    {
        $customer = $support->getCustomer();
        $order = $support->getOrder();
        return [
            'id' => $support->getId(),
            'status' => $support->getStatus(),
            'statusLabel' => $this->supportStatusLabel($support->getStatus()),
            'reason' => $support->getReason(),
            'subject' => $support->getSubject(),
            'message' => $support->getMessage(),
            'internalNotes' => $support->getInternalNotes(),
            'customer' => ['id' => $customer->getId(), 'name' => $customer->getFullName(), 'email' => $customer->getEmail()],
            'order' => $order instanceof Order ? ['id' => $order->getId(), 'number' => $order->getNumber()] : null,
            'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $support->getUpdatedAt()->format(DATE_ATOM),
            'resolvedAt' => $support->getResolvedAt()?->format(DATE_ATOM),
        ];
    }

    private function formatRefund(RefundRequest $refund): array
    {
        $order = $refund->getOrder();
        return [
            'id' => $refund->getId(),
            'order' => ['id' => $order->getId(), 'number' => $order->getNumber()],
            'paymentId' => $refund->getPaymentId(),
            'amountCents' => $refund->getAmountCents(),
            'currencyCode' => $refund->getCurrencyCode(),
            'status' => $refund->getStatus(),
            'reason' => $refund->getReason(),
            'internalNotes' => $refund->getInternalNotes(),
            'stripeRefundId' => $refund->getStripeRefundId(),
            'createdAt' => $refund->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $refund->getUpdatedAt()->format(DATE_ATOM),
        ];
    }

    private function formatStockMovement(StockMovement $movement): array
    {
        $product = $movement->getProduct();
        return [
            'id' => $movement->getId(),
            'product' => ['id' => $product->getId(), 'name' => $product->getName(), 'sku' => $product->getSku()],
            'delta' => $movement->getDelta(),
            'stockBefore' => $movement->getStockBefore(),
            'stockAfter' => $movement->getStockAfter(),
            'reason' => $movement->getReason(),
            'note' => $movement->getNote(),
            'actor' => $movement->getActor()?->getFullName(),
            'createdAt' => $movement->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    private function formatLowStockProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'lowStockThreshold' => $product->getLowStockThreshold(),
            'category' => $product->getCategory()->getName(),
        ];
    }

    private function formatFulfillmentOrder(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'number' => $order->getNumber(),
            'status' => $order->getStatus(),
            'statusLabel' => OrderFormatter::formatStatusLabel($order->getStatus()),
            'customer' => [
                'id' => $order->getUser()->getId(),
                'name' => $order->getUser()->getFullName(),
                'email' => $order->getUser()->getEmail(),
            ],
            'totalPriceCents' => $order->getTotalPriceCents(),
            'shipping' => [
                'name' => $order->getShippingName(),
                'address' => $order->getShippingAddress(),
                'postalCode' => $order->getShippingPostalCode(),
                'city' => $order->getShippingCity(),
            ],
            'delivery' => [
                'status' => $order->getDeliveryStatus(),
                'statusLabel' => OrderFormatter::formatDeliveryStatusLabel($order->getDeliveryStatus()),
                'carrier' => $order->getDeliveryCarrier(),
                'trackingNumber' => $order->getDeliveryTrackingNumber(),
                'trackingUrl' => $order->getDeliveryTrackingUrl(),
            ],
            'items' => array_map(static fn (OrderItem $item): array => [
                'name' => $item->getProductName(),
                'sku' => $item->getProductSku(),
                'quantity' => $item->getQuantity(),
            ], $order->getItems()->toArray()),
            'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    private function findPaymentIntentForOrder(Order $order): ?string
    {
        $payments = $this->payments->findBy(['orderId' => $order->getId()], ['createdAt' => 'DESC'], 5);
        foreach ($payments as $payment) {
            $paymentIntentId = $payment->getStripePaymentIntentId();
            if ($paymentIntentId !== null && $paymentIntentId !== '') {
                return $paymentIntentId;
            }
        }

        return null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function buildEmailLogs(): array
    {
        $items = [];
        foreach ($this->orders->findBy([], ['createdAt' => 'DESC'], 80) as $order) {
            foreach ([
                'order_created' => $order->getOrderCreatedEmailSentAt(),
                'invoice' => $order->getInvoiceEmailSentAt(),
                'status_confirmed' => $order->getStatusConfirmedEmailSentAt(),
                'status_delivered' => $order->getStatusDeliveredEmailSentAt(),
                'status_cancelled' => $order->getStatusCancelledEmailSentAt(),
            ] as $scenario => $sentAt) {
                if ($sentAt !== null) {
                    $items[] = [
                        'type' => 'transactional',
                        'scenario' => $scenario,
                        'scenarioLabel' => $this->emailScenarioLabel($scenario),
                        'status' => 'sent',
                        'statusLabel' => 'Envoyé',
                        'recipient' => $order->getBillingEmail() ?? $order->getUser()->getEmail(),
                        'subject' => 'Commande ' . $order->getNumber(),
                        'related' => ['type' => 'order', 'id' => $order->getId(), 'label' => $order->getNumber()],
                        'createdAt' => $sentAt->format(DATE_ATOM),
                    ];
                }
            }
        }
        foreach ($this->orderEvents->findBy(['type' => 'email_failed'], ['createdAt' => 'DESC'], 80) as $event) {
            $items[] = [
                'type' => 'transactional',
                'scenario' => 'email_failed',
                'scenarioLabel' => 'Email non envoyé',
                'status' => 'failed',
                'statusLabel' => 'Échec',
                'recipient' => null,
                'subject' => $event->getMessage(),
                'related' => ['type' => 'order', 'id' => $event->getOrder()->getId(), 'label' => $event->getOrder()->getNumber()],
                'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $b['createdAt'], (string) $a['createdAt']));

        return $items;
    }

    private function emailScenarioLabel(string $scenario): string
    {
        return match ($scenario) {
            'order_created' => 'Confirmation de commande',
            'invoice' => 'Facture envoyée',
            'status_confirmed' => 'Commande confirmée',
            'status_delivered', 'order_status_delivered' => 'Commande livrée',
            'status_cancelled', 'order_status_cancelled' => 'Commande annulée',
            'customer_voucher_offer' => 'Bon de réduction client',
            'email_failed' => 'Email non envoyé',
            default => ucfirst(str_replace('_', ' ', $scenario)),
        };
    }

    private function supportStatusLabel(string $status): string
    {
        return match ($status) {
            SupportRequest::STATUS_NEW => 'Nouveau',
            SupportRequest::STATUS_IN_PROGRESS => 'En cours',
            SupportRequest::STATUS_WAITING_CUSTOMER => 'En attente client',
            SupportRequest::STATUS_RESOLVED => 'Résolu',
            SupportRequest::STATUS_REFUSED => 'Refusé',
            default => $status,
        };
    }

    private function exportOrders(): array
    {
        $rows = [['id', 'numero', 'client', 'email', 'statut', 'total_centimes', 'date']];
        foreach ($this->orders->findBy([], ['createdAt' => 'DESC']) as $order) {
            $rows[] = [$order->getId(), $order->getNumber(), $order->getUser()->getFullName(), $order->getUser()->getEmail(), $order->getStatus(), $order->getTotalPriceCents(), $order->getCreatedAt()->format(DATE_ATOM)];
        }
        return $rows;
    }

    private function exportCustomers(): array
    {
        $rows = [['id', 'nom', 'email', 'telephone', 'verifie', 'date_creation']];
        foreach ($this->users->findBy([], ['createdAt' => 'DESC']) as $user) {
            $rows[] = [$user->getId(), $user->getFullName(), $user->getEmail(), $user->getPhoneNumber(), $user->isVerified() ? 'oui' : 'non', $user->getCreatedAt()->format(DATE_ATOM)];
        }
        return $rows;
    }

    private function exportProducts(): array
    {
        $rows = [['id', 'sku', 'nom', 'stock', 'prix_centimes', 'publie']];
        foreach ($this->products->findBy([], ['updatedAt' => 'DESC']) as $product) {
            $rows[] = [$product->getId(), $product->getSku(), $product->getName(), $product->getStock(), $product->getPriceCents(), $product->isPublished() ? 'oui' : 'non'];
        }
        return $rows;
    }

    private function exportQuotes(): array
    {
        $rows = [['id', 'numero', 'client', 'email', 'statut', 'date']];
        foreach ($this->quotes->findBy([], ['createdAt' => 'DESC']) as $quote) {
            $rows[] = [$quote->getId(), $quote->getNumber(), $quote->getCustomerName(), $quote->getCustomerEmail(), $quote->getStatus(), $quote->getCreatedAt()->format(DATE_ATOM)];
        }
        return $rows;
    }

    private function exportRefunds(): array
    {
        $rows = [['id', 'commande', 'montant_centimes', 'statut', 'motif', 'stripe_refund_id', 'date']];
        foreach ($this->refunds->findBy([], ['createdAt' => 'DESC']) as $refund) {
            $rows[] = [$refund->getId(), $refund->getOrder()->getNumber(), $refund->getAmountCents(), $refund->getStatus(), $refund->getReason(), $refund->getStripeRefundId(), $refund->getCreatedAt()->format(DATE_ATOM)];
        }
        return $rows;
    }

    private function exportSupport(): array
    {
        $rows = [['id', 'client', 'email', 'commande', 'statut', 'motif', 'sujet', 'date']];
        foreach ($this->supportRequests->findBy([], ['createdAt' => 'DESC']) as $support) {
            $rows[] = [$support->getId(), $support->getCustomer()->getFullName(), $support->getCustomer()->getEmail(), $support->getOrder()?->getNumber(), $support->getStatus(), $support->getReason(), $support->getSubject(), $support->getCreatedAt()->format(DATE_ATOM)];
        }
        return $rows;
    }
}
