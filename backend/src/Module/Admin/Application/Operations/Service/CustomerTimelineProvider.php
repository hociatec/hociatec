<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Service;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\Support\Infrastructure\Repository\SupportRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;

final readonly class CustomerTimelineProvider
{
    public function __construct(
        private UserRepository $users,
        private OrderRepository $orders,
        private SupportRequestRepository $supportRequests,
        private QuoteRepository $quotes,
        private AdminOperationsFormatter $formatter,
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
        return array_map(static fn ($order): array => [
            'type' => 'order',
            'label' => 'Commande '.$order->getNumber(),
            'description' => OrderFormatter::formatStatusLabel($order->getStatus()).' · '.$order->getTotalPriceCents() / 100 .' €',
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
