<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\Workflow;

use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\Order\Application\Support\RentalPeriodCalculator;
use App\Module\Order\Domain\Entity\Order;
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
        private RentalExtensionCheckoutService $extensions,
        private CustomerRentalTerminationService $termination,
        private RentalExtensionPaymentReconciliationService $extensionPayments,
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
        $this->extensionPayments->reconcileCollection(array_merge($upcoming, $past));

        return [
            'upcoming' => $this->formatRentals($upcoming, $today),
            'past' => $this->formatRentals($past, $today),
            'upcomingTotal' => $this->orderItems->countUpcomingRentalsForUser($user, $today),
            'pastTotal' => $this->orderItems->countPastRentalsForUser($user, $today),
        ];
    }

    /**
     * @return array{rental:array<string,mixed>,checkout?:array<string,mixed>}|null
     */
    public function requestChangeForUser(User $user, int $orderItemId, string $action, ?string $requestedEndDate, ?string $clientPlatform = null): ?array
    {
        $item = $this->findRentalForUser($user, $orderItemId);
        if (!$item instanceof OrderItem) {
            return null;
        }
        $this->assertClientRentalMutable($item);

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

        if ('extend' === $action) {
            return $this->prepareExtensionChange($user, $item, $currentEndDate, $currentStartDate, $requestedDate, $clientPlatform, $today);
        }
        if ('end_early' === $action) {
            $this->assertEarlyTerminationDate($requestedDate, $currentStartDate, $currentEndDate);
        }
        $item->requestRentalChange($action, $requestedDate);
        $this->persistence->persist($item);
        $this->persistence->flush();

        return ['rental' => $this->formatRental($item, $today)];
    }

    /**
     * @return array{rental:array<string,mixed>}|null
     */
    public function scheduleReturnForUser(User $user, int $orderItemId, string $mode, ?string $requestedDate): ?array
    {
        $item = $this->findRentalForUser($user, $orderItemId);
        if (!$item instanceof OrderItem) {
            return null;
        }
        $this->assertClientRentalMutable($item);

        $date = RentalPeriodCalculator::parseDate($requestedDate);
        if (null === $date) {
            throw new \InvalidArgumentException('La date de restitution demandée est invalide.');
        }

        $today = new \DateTimeImmutable('today');
        if ($date < $today) {
            throw new \InvalidArgumentException('La date de restitution doit être aujourd\'hui ou dans le futur.');
        }

        $startDate = $item->getRentalStartDate();
        $endDate = $item->getRentalEndDate();
        if ((null !== $startDate && $date < $startDate) || (null !== $endDate && $date > $endDate)) {
            throw new \InvalidArgumentException('Choisissez une date comprise dans la période de location.');
        }

        $item->scheduleRentalReturn($mode, $date);
        $this->persistence->persist($item);
        $this->persistence->flush();

        return ['rental' => $this->formatRental($item, $today)];
    }

    /**
     * @return array{rental:array<string,mixed>}|null
     */
    public function terminateForUser(
        User $user,
        int $orderItemId,
        ?string $requestedEndDate,
        string $returnMode,
        ?string $returnRequestedDate,
    ): ?array {
        $item = $this->findRentalForUser($user, $orderItemId);
        if (!$item instanceof OrderItem) {
            return null;
        }
        $this->assertClientRentalMutable($item);

        $today = new \DateTimeImmutable('today');
        $this->termination->terminate($item, $requestedEndDate, $returnMode, $returnRequestedDate, $today);
        $this->persistence->persist($item);
        $this->persistence->flush();

        return ['rental' => $this->formatRental($item, $today)];
    }

    public function applyPaidExtensionOrder(Order $order): void
    {
        $this->extensionPayments->applyPaidExtensionOrder($order);
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

        return 'rental' === $item->getSellingType() ? $item : null;
    }

    private function assertClientRentalMutable(OrderItem $item): void
    {
        if ('completed' === $item->getRentalReturnStatus()) {
            throw new \DomainException('Cette location a déjà été restituée et ne peut plus être modifiée.');
        }
    }

    /** @return array{rental:array<string,mixed>,checkout:array<string,mixed>} */
    private function prepareExtensionChange(
        User $user,
        OrderItem $item,
        \DateTimeImmutable $currentEndDate,
        \DateTimeImmutable $currentStartDate,
        \DateTimeImmutable $requestedDate,
        ?string $clientPlatform,
        \DateTimeImmutable $today,
    ): array {
        if ($requestedDate <= $currentEndDate) {
            throw new \InvalidArgumentException('Une prolongation doit demander une date de fin postérieure à la fin actuelle.');
        }

        $coveredMonths = RentalPeriodCalculator::findMinimumMonthsCoveringEndDate($currentStartDate, $requestedDate);
        if (null === $coveredMonths) {
            throw new \InvalidArgumentException('La nouvelle échéance demandée est invalide.');
        }

        $additionalMonths = $coveredMonths - max(1, (int) ($item->getRentalMonths() ?? 1));
        if ($additionalMonths < 1) {
            throw new \InvalidArgumentException('Cette prolongation ne crée aucun mois supplémentaire à facturer.');
        }

        return $this->extensions->prepare($user, $item, $additionalMonths, $requestedDate, $clientPlatform, $today);
    }

    private function assertEarlyTerminationDate(
        \DateTimeImmutable $requestedDate,
        \DateTimeImmutable $currentStartDate,
        \DateTimeImmutable $currentEndDate,
    ): void {
        if ($requestedDate >= $currentEndDate) {
            throw new \InvalidArgumentException('Une fin anticipée doit demander une date de fin antérieure à la fin actuelle.');
        }

        if ($requestedDate < $currentStartDate) {
            throw new \InvalidArgumentException('La date de fin demandée ne peut pas être antérieure au début de location.');
        }
    }

    /**
     * @param list<OrderItem> $items
     *
     * @return list<array<string,mixed>>
     */
    private function formatRentals(array $items, \DateTimeImmutable $today): array
    {
        return array_map(
            fn (OrderItem $item): array => $this->formatRental($this->extensionPayments->reload($item), $today),
            $items,
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function formatRental(OrderItem $item, \DateTimeImmutable $today): array
    {
        $payload = $this->formatter->format($item, $today);
        $extensionOrderId = $item->getRentalExtensionOrderId();
        $checkout = null !== $extensionOrderId ? $this->extensionPayments->latestCheckoutSessionForOrder($extensionOrderId) : null;

        $payload['extension']['checkoutSessionId'] = $checkout?->getStripeSessionId();
        $payload['extension']['checkoutUrl'] = $checkout?->getCheckoutUrl();
        $payload['extension']['checkoutStatus'] = $checkout?->getStatus();

        return $payload;
    }
}
