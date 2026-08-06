<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Conversion;

use App\Module\Quote\Application\Conversion\DTO\QuoteConversionResult;
use App\Module\Quote\Application\Conversion\Exception\QuoteConversionResourceNotFoundException;
use App\Shared\Application\UnitOfWork;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;

final readonly class QuoteToOrderConverter
{
    public function __construct(
        private QuoteRepositoryPort $quotes,
        private UserRepositoryPort $users,
        private UnitOfWork $persistence,
        private TransactionManager $transactions,
        private QuoteConversionServices $services,
    ) {
    }

    public function convert(string $reference): QuoteConversionResult
    {
        $quote = $this->findQuote(trim($reference));
        $this->services->assertConvertible($quote);
        $customer = $this->resolveCustomer($quote);

        $order = $this->transactions->transactional(function () use ($quote, $customer) {
            $order = $this->services->createOrder($quote, $customer);
            $this->persistence->persist($order);
            $this->persistence->commit();
            if (null === $order->getId()) {
                throw new \RuntimeException('La commande n\'a pas d\'identifiant après enregistrement.');
            }

            $quote->convertToOrder($order->getId(), $order->getNumber());
            $this->persistence->commit();

            return $order;
        });

        return $this->services->result($order);
    }

    private function findQuote(string $reference): Quote
    {
        $quote = ctype_digit($reference)
            ? $this->quotes->find((int) $reference)
            : $this->quotes->findOneBy(['number' => $reference]);

        if (!$quote instanceof Quote) {
            throw new QuoteConversionResourceNotFoundException('Devis introuvable.');
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
