<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;

final readonly class QuoteToOrderConverter
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private UserRepositoryPort $users,
        private OperationsPersistence $persistence,
        private OrderFormatter $orderFormatter,
        private QuoteConversionPolicy $policy,
        private QuoteOrderFactory $orderFactory,
        private QuoteConversionNotifier $notifier,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function convert(string $reference): array
    {
        $quote = $this->findQuote(trim($reference));
        $this->policy->assertConvertible($quote);
        $customer = $this->resolveCustomer($quote);
        $order = $this->orderFactory->create($quote, $customer);

        $this->persistence->persist($order);
        $this->persistence->commit();
        if (null === $order->getId()) {
            throw new \RuntimeException('La commande n\'a pas d\'identifiant après enregistrement.');
        }

        $quote->convertToOrder($order->getId(), $order->getNumber());
        $this->persistence->commit();

        [$emailSent, $emailError] = $this->notifier->sendOrderCreated($order);

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
}
