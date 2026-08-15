<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\Workflow;

use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\Order\Application\Support\RentalPeriodCalculator;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rental\Application\Projection\RentalFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class CustomerRentalPortalService
{
    public function __construct(
        private OrderItemRepositoryPort $orderItems,
        private RentalFormatter $formatter,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @return array{
     *   upcoming:list<array<string,mixed>>,
     *   past:list<array<string,mixed>>,
     *   upcomingTotal:int,
     *   pastTotal:int
     * }
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $today = new \DateTimeImmutable('today');
        $upcoming = $this->orderItems->findUpcomingRentalsForUser($user, $today, $limit, $offset);
        $past = $this->orderItems->findPastRentalsForUser($user, $today, $limit, $offset);

        return [
            'upcoming' => array_map(fn (OrderItem $item): array => $this->formatter->format($item, $today), $upcoming),
            'past' => array_map(fn (OrderItem $item): array => $this->formatter->format($item, $today), $past),
            'upcomingTotal' => $this->orderItems->countUpcomingRentalsForUser($user, $today),
            'pastTotal' => $this->orderItems->countPastRentalsForUser($user, $today),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function requestChangeForUser(User $user, int $orderItemId, string $action, ?string $requestedEndDate): ?array
    {
        $item = $this->findRentalForUser($user, $orderItemId);
        if (!$item instanceof OrderItem) {
            return null;
        }

        $currentEndDate = $item->getRentalEndDate();
        $currentStartDate = $item->getRentalStartDate();
        if (null === $currentEndDate || null === $currentStartDate) {
            throw new \InvalidArgumentException('Cette location est incomplète et ne peut pas être modifiée.');
        }

        $requestedDate = RentalPeriodCalculator::parseDate($requestedEndDate);
        if (null === $requestedDate) {
            throw new \InvalidArgumentException('La nouvelle date de fin demandée est invalide.');
        }

        $today = new \DateTimeImmutable('today');
        if ($requestedDate < $today) {
            throw new \InvalidArgumentException('La date demandée doit être aujourd\'hui ou dans le futur.');
        }

        if ('extend' === $action && $requestedDate <= $currentEndDate) {
            throw new \InvalidArgumentException('Une prolongation doit demander une date de fin postérieure à la fin actuelle.');
        }

        if ('extend' === $action) {
            $alignedMonths = RentalPeriodCalculator::findAlignedMonthsForEndDate($currentStartDate, $requestedDate);
            if (null === $alignedMonths) {
                throw new \InvalidArgumentException('Pour une prolongation automatique, choisissez une date correspondant à une échéance de location valide.');
            }

            $this->applyAutomaticExtension($item, $alignedMonths, $requestedDate);

            return $this->formatter->format($item, $today);
        }

        if ('end_early' === $action) {
            if ($requestedDate >= $currentEndDate) {
                throw new \InvalidArgumentException('Une fin anticipée doit demander une date de fin antérieure à la fin actuelle.');
            }
            if ($requestedDate < $currentStartDate) {
                throw new \InvalidArgumentException('La date de fin demandée ne peut pas être antérieure au début de location.');
            }
        }

        $item->requestRentalChange($action, $requestedDate);
        $this->persistence->persist($item);
        $this->persistence->flush();

        return $this->formatter->format($item, $today);
    }

    private function findRentalForUser(User $user, int $orderItemId): ?OrderItem
    {
        $item = $this->orderItems->findById($orderItemId);
        if (!$item instanceof OrderItem) {
            return null;
        }

        if ($item->getOrder()?->getUser()?->getId() !== $user->getId()) {
            throw new \DomainException('Vous n\'êtes pas autorisé à modifier cette location.');
        }

        if ('rental' !== $item->getSellingType()) {
            return null;
        }

        return $item;
    }

    private function applyAutomaticExtension(OrderItem $item, int $alignedMonths, \DateTimeImmutable $requestedDate): void
    {
        $order = $item->getOrder();
        if (null === $order) {
            throw new \LogicException('La commande associée à cette location est introuvable.');
        }

        $item->applyApprovedRentalExtension($requestedDate, $alignedMonths);
        $this->recalculateRentalLineTotals($item, $alignedMonths);
        $this->recalculateOrderTotals($order);

        $this->persistence->persist($item);
        $this->persistence->persist($order);
        $this->persistence->flush();
    }

    private function recalculateRentalLineTotals(OrderItem $item, int $alignedMonths): void
    {
        $quantity = max(1, $item->getQuantity());
        $grossLineTotal = $item->getUnitPriceCents() * $quantity * max(1, $alignedMonths);
        $vatRate = max(0, $item->getVatRateBps());
        $lineSubtotalHt = 0 === $vatRate
            ? $grossLineTotal
            : (int) round($grossLineTotal / (1 + ($vatRate / 10000)));
        $lineVat = max(0, $grossLineTotal - $lineSubtotalHt);

        $item->replaceLineTotals($lineSubtotalHt, $lineVat, $grossLineTotal);
    }

    private function recalculateOrderTotals(\App\Module\Order\Domain\Entity\Order $order): void
    {
        $subtotal = 0;

        foreach ($order->getItems() as $line) {
            $subtotal += $line->getLinePriceCents();
        }

        $discount = max(0, $order->getDiscountAmountCents());
        $order->replacePaymentAmounts($subtotal, $discount, max(0, $subtotal - $discount));
    }
}
