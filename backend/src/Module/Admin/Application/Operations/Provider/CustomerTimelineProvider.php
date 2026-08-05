<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Provider;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerTimelineProvider
{
    public function __construct(
        private UserRepositoryPort $users,
        private OrderRepositoryPort $orders,
        private SupportRequestRepositoryPort $supportRequests,
        private QuoteRepositoryPort $quotes,
        private AdminOperationsFormatter $formatter,
        private OrderFormatter $orderFormatter,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function provide(int $customerId): array
    {
        $customer = $this->users->find($customerId);
        if (!$customer instanceof User) {
            throw new OperationsResourceNotFoundException('Client introuvable.');
        }

        $items = [
            ...$this->orders($customer),
            ...$this->support($customer),
            ...$this->quotes($customer),
        ];
        usort($items, static fn (array $left, array $right): int => strcmp(
            (string) $right['date'],
            (string) $left['date'],
        ));

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function orders(User $customer): array
    {
        return array_map(fn ($order): array => [
            'type' => 'order',
            'label' => 'Commande '.$order->getNumber(),
            'description' => $this->orderFormatter->formatStatusLabel($order->getStatus()).' · '.$order->getTotalPriceCents() / 100 .' €',
            'date' => $order->getCreatedAt()->format(DATE_ATOM),
            'href' => '/admin/orders/'.$order->getId(),
        ], $this->orders->findBy(['user' => $customer], ['createdAt' => 'DESC']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function support(User $customer): array
    {
        return array_map(fn ($support): array => [
            'type' => 'support',
            'label' => 'SAV #'.$support->getId().' · '.$support->getSubject(),
            'description' => $this->formatter->supportStatusLabel($support->getStatus()),
            'date' => $support->getCreatedAt()->format(DATE_ATOM),
            'href' => '/admin/operations',
        ], $this->supportRequests->findBy(['customer' => $customer], ['updatedAt' => 'DESC']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function quotes(User $customer): array
    {
        return array_map(static fn ($quote): array => [
            'type' => 'quote',
            'label' => 'Devis '.$quote->getNumber(),
            'description' => $quote->getStatus(),
            'date' => $quote->getCreatedAt()->format(DATE_ATOM),
            'href' => '/admin/quotes/'.$quote->getId(),
        ], $this->quotes->findBy(['customerEmail' => $customer->getEmail()], ['createdAt' => 'DESC']));
    }
}
