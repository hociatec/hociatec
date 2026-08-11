<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class UserPersonalDataAnonymizer
{
    public function __construct(
        private OrderRepositoryPort $orders,
        private TradeInRequestRepositoryPort $tradeIns,
        private QuoteRepositoryPort $quotes,
        private UnitOfWork $persistence,
    ) {
    }

    public function shouldRetainHistory(User $user): bool
    {
        return $this->orders->countByUser($user) > 0
            || $this->tradeIns->countByUser($user) > 0
            || $this->quotes->countByCustomerEmail($user->getEmail()) > 0;
    }

    public function anonymize(User $user): void
    {
        foreach ($this->orders->findByUser($user, 1000) as $order) {
            $order->anonymizePersonalData();
        }

        foreach ($this->tradeIns->findByUser($user, 1000) as $request) {
            $request->anonymizePersonalData();
        }

        foreach ($this->quotes->findByCustomerEmail($user->getEmail(), 1000) as $quote) {
            $quote->anonymizePersonalData();
        }

        $user->anonymize($this->anonymizedEmail($user));
        $this->persistence->persist($user);
    }

    private function anonymizedEmail(User $user): string
    {
        $suffix = null !== $user->getId() ? (string) $user->getId() : bin2hex(random_bytes(6));

        return sprintf('deleted+user-%s@privacy.invalid', $suffix);
    }
}
