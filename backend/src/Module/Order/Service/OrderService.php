<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Cart\Entity\CartSession;
use App\Module\Catalog\Entity\Product;
use App\Module\Order\Entity\Order;
use App\Module\Order\Entity\OrderCheckoutSession;
use App\Module\Order\Entity\OrderItem;
use App\Module\Order\Message\OrderCreatedMessage;
use App\Module\Promotion\Service\PromotionEngine;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\Voucher\Service\VoucherEngine;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderNumberGenerator $numberGenerator,
        private readonly InvoiceNumberGenerator $invoiceNumberGenerator,
        private readonly OrderInvoiceCalculator $invoiceCalculator,
        private readonly OrderInvoiceDocumentService $invoiceDocuments,
        private readonly OrderNotificationEmailService $notificationEmails,
        private readonly OrderEventLogger $eventLogger,
        private readonly MessageBusInterface $bus,
        private readonly PromotionEngine $promotionEngine,
        private readonly VoucherEngine $voucherEngine,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createFromCart(User $user, CartSession $cart): Order
    {
        throw new InvalidArgumentException('Adresse de livraison requise. Utiliser createFromCartWithAddress().');
    }

    public function createFromCartWithAddress(User $user, CartSession $cart, ShippingAddress $address): Order
    {
        if ($cart->getItems()->count() === 0) {
            throw new InvalidArgumentException('Le panier est vide.');
        }

        $promotionSummary = $this->promotionEngine->calculateCartSummary($cart, $user);
        $voucherSummary = $this->voucherEngine->calculateCartSummary($cart, $user, $cart->getVoucherCode());
        $cartSummary = ($cart->getVoucherCode() !== null && ($voucherSummary['voucherCodeStatus'] ?? 'none') === 'applied')
            ? $voucherSummary
            : $promotionSummary;

        $order = $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($user, $cart, $address, $cartSummary): Order {
            $cartId = $cart->getId();
            if ($cartId === null) {
                throw new InvalidArgumentException('Panier invalide.');
            }

            /** @var CartSession|null $lockedCart */
            $lockedCart = $em->find(CartSession::class, $cartId, LockMode::PESSIMISTIC_WRITE);
            if ($lockedCart === null) {
                throw new InvalidArgumentException('Panier introuvable.');
            }

            if ($lockedCart->isConverted()) {
                throw new InvalidArgumentException('Ce panier a deja ete valide.');
            }

            if ($lockedCart->getItems()->count() === 0) {
                throw new InvalidArgumentException('Le panier est vide.');
            }

            $issuedAt = new \DateTimeImmutable();
            $order = new Order($this->numberGenerator->generate(), $user);
            $customerFullName = trim($user->getFirstName() . ' ' . $user->getLastName());

            $order
                ->setStatus(Order::STATUS_CONFIRMED)
                ->setShippingName($customerFullName !== '' ? $customerFullName : $address->getName())
                ->setShippingAddress($address->getAddress())
                ->setShippingPostalCode($address->getPostalCode())
                ->setShippingCity($address->getCity())
                ->setBillingName($customerFullName !== '' ? $customerFullName : $address->getName())
                ->setBillingCompany($address->getCompany())
                ->setBillingCompanySiren($address->getCompanySiren())
                ->setBillingCompanyVatNumber($address->getCompanyVatNumber())
                ->setPurchaseOrderNumber($address->getPurchaseOrderNumber())
                ->setBillingAddress($address->getAddress())
                ->setBillingPostalCode($address->getPostalCode())
                ->setBillingCity($address->getCity())
                ->setBillingEmail($user->getEmail())
                ->setInvoiceNumber($this->invoiceNumberGenerator->generate())
                ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
                ->setInvoicedAt($issuedAt)
                ->setCurrencyCode('EUR')
                ->setElectronicFormat('UBL-2.1')
                ->setSubtotalPriceCents((int) $cartSummary['subtotalPriceCents'])
                ->setDiscountAmountCents((int) $cartSummary['discountAmountCents'])
                ->setTotalPriceCents((int) $cartSummary['totalPriceCents'])
                ->setAppliedPromotionName($cartSummary['appliedVoucher']['name'] ?? ($cartSummary['appliedPromotion']['name'] ?? null))
                ->setAppliedPromotionSlug($cartSummary['appliedVoucher']['code'] ?? ($cartSummary['appliedPromotion']['slug'] ?? null));

            foreach ($lockedCart->getItems() as $cartItem) {
                $product = $cartItem->getProduct();
                $productId = $product->getId();
                if ($productId === null) {
                    throw new InvalidArgumentException('Produit invalide.');
                }

                /** @var Product|null $lockedProduct */
                $lockedProduct = $em->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                if ($lockedProduct === null) {
                    throw new InvalidArgumentException('Produit introuvable.');
                }

                $quantity = $cartItem->getQuantity();
                $currentStock = $lockedProduct->getStock();
                if ($quantity > $currentStock) {
                    throw new InvalidArgumentException('Stock insuffisant pour le produit ' . $lockedProduct->getSku() . '.');
                }

                $lockedProduct->setStock($currentStock - $quantity);

                $item = new OrderItem(
                    $lockedProduct->getName(),
                    $lockedProduct->getSku(),
                    $lockedProduct->getPriceCents(),
                    $quantity,
                );
                $item
                    ->setProduct($lockedProduct)
                    ->setVatRateBps(2000);

                $order->addItem($item);
                $em->persist($item);
            }

            $this->invoiceCalculator->snapshot($order);

            $em->persist($order);
            $em->flush();

            $orderId = $order->getId();
            if ($orderId === null) {
                throw new InvalidArgumentException('Commande invalide.');
            }

            $lockedCart->markConverted($orderId);
            $em->persist($lockedCart);
            $em->flush();

            return $order;
        });

        $this->eventLogger->log($order, $user, 'order_created', 'Commande créée et confirmée automatiquement.');
        $this->bus->dispatch(new OrderCreatedMessage($order->getId() ?? 0, $order->getNumber(), $user->getId() ?? 0));

        try {
            $this->invoiceDocuments->ensureGenerated($order);
            $this->eventLogger->log($order, $user, 'invoice_generated', 'Facture PDF/XML générée.');
            $this->notificationEmails->sendOrderCreatedIfNeeded($order);
        } catch (\Throwable $exception) {
            $this->eventLogger->log($order, $user, 'post_processing_failed', 'Échec post-commande: ' . $exception->getMessage());
            $this->logger->error('Order post-processing failed after checkout.', [
                'order_id' => $order->getId(),
                'order_number' => $order->getNumber(),
                'error' => $exception->getMessage(),
            ]);
        }

        return $order;
    }

    public function createFromCheckoutSession(OrderCheckoutSession $checkout): Order
    {
        $user = $checkout->getUser();

        $order = $this->em->wrapInTransaction(function (EntityManagerInterface $em) use ($checkout, $user): Order {
            if ($checkout->getOrderId() !== null) {
                $existingOrder = $em->find(Order::class, $checkout->getOrderId());
                if ($existingOrder instanceof Order) {
                    return $existingOrder;
                }
            }

            $order = new Order($this->numberGenerator->generate(), $user);
            $issuedAt = new \DateTimeImmutable();

            $order
                ->setStatus(Order::STATUS_CONFIRMED)
                ->setShippingName($checkout->getShippingName())
                ->setShippingAddress($checkout->getShippingAddress())
                ->setShippingPostalCode($checkout->getShippingPostalCode())
                ->setShippingCity($checkout->getShippingCity())
                ->setBillingName($checkout->getBillingName())
                ->setBillingCompany($checkout->getBillingCompany())
                ->setBillingCompanySiren($checkout->getBillingCompanySiren())
                ->setBillingCompanyVatNumber($checkout->getBillingCompanyVatNumber())
                ->setPurchaseOrderNumber($checkout->getPurchaseOrderNumber())
                ->setBillingAddress($checkout->getBillingAddress())
                ->setBillingPostalCode($checkout->getBillingPostalCode())
                ->setBillingCity($checkout->getBillingCity())
                ->setBillingEmail($checkout->getBillingEmail())
                ->setInvoiceNumber($this->invoiceNumberGenerator->generate())
                ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
                ->setInvoicedAt($issuedAt)
                ->setCurrencyCode($checkout->getCurrencyCode())
                ->setElectronicFormat('UBL-2.1')
                ->setSubtotalPriceCents($checkout->getSubtotalPriceCents())
                ->setDiscountAmountCents($checkout->getDiscountAmountCents())
                ->setTotalPriceCents($checkout->getTotalPriceCents())
                ->setAppliedPromotionName($checkout->getAppliedPromotionName())
                ->setAppliedPromotionSlug($checkout->getAppliedPromotionSlug());

            foreach ($checkout->getItemsPayload() as $rawItem) {
                $productId = (int) ($rawItem['productId'] ?? 0);
                if ($productId <= 0) {
                    throw new InvalidArgumentException('Produit Stripe invalide.');
                }

                /** @var Product|null $lockedProduct */
                $lockedProduct = $em->find(Product::class, $productId, LockMode::PESSIMISTIC_WRITE);
                if ($lockedProduct === null) {
                    throw new InvalidArgumentException('Produit introuvable.');
                }

                $quantity = max(1, (int) ($rawItem['quantity'] ?? 1));
                $currentStock = $lockedProduct->getStock();
                if ($quantity > $currentStock) {
                    throw new InvalidArgumentException('Stock insuffisant pour le produit ' . $lockedProduct->getSku() . '.');
                }

                $lockedProduct->setStock($currentStock - $quantity);

                $item = new OrderItem(
                    (string) ($rawItem['productName'] ?? $lockedProduct->getName()),
                    (string) ($rawItem['productSku'] ?? $lockedProduct->getSku()),
                    max(0, (int) ($rawItem['unitPriceCents'] ?? $lockedProduct->getPriceCents())),
                    $quantity,
                );
                $item
                    ->setProduct($lockedProduct)
                    ->setVatRateBps(max(0, (int) ($rawItem['vatRateBps'] ?? 2000)));

                $order->addItem($item);
                $em->persist($item);
            }

            $this->invoiceCalculator->snapshot($order);

            $em->persist($order);
            $em->flush();

            $orderId = $order->getId();
            if ($orderId === null) {
                throw new InvalidArgumentException('Commande invalide.');
            }

            if ($checkout->getCartId() !== null) {
                /** @var CartSession|null $cart */
                $cart = $em->find(CartSession::class, $checkout->getCartId(), LockMode::PESSIMISTIC_WRITE);
                if ($cart !== null && !$cart->isConverted()) {
                    $cart->markConverted($orderId);
                    $em->persist($cart);
                    $em->flush();
                }
            }

            $checkout->setOrderId($orderId);
            $em->persist($checkout);
            $em->flush();

            return $order;
        });

        $this->eventLogger->log($order, $user, 'payment_confirmed', 'Paiement Stripe confirmé.');
        $this->eventLogger->log($order, $user, 'order_created', 'Commande créée après paiement Stripe.');

        $this->bus->dispatch(new OrderCreatedMessage($order->getId() ?? 0, $order->getNumber(), $user->getId() ?? 0));

        try {
            $this->invoiceDocuments->ensureGenerated($order);
            $this->eventLogger->log($order, $user, 'invoice_generated', 'Facture PDF/XML générée.');
            $this->notificationEmails->sendOrderCreatedIfNeeded($order);
        } catch (\Throwable $exception) {
            $this->eventLogger->log($order, $user, 'post_processing_failed', 'Échec post-paiement: ' . $exception->getMessage());
            $this->logger->error('Order post-processing failed after Stripe payment.', [
                'order_id' => $order->getId(),
                'order_number' => $order->getNumber(),
                'error' => $exception->getMessage(),
            ]);
        }

        return $order;
    }
}
