<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Rental\Workflow;

use App\Module\Admin\Application\Rental\Projection\AdminRentalFormatter;
use App\Module\Order\Application\Port\OrderItemRepositoryPort;
use App\Module\Order\Application\Support\RentalPeriodCalculator;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Shared\Application\UnitOfWork;

final readonly class AdminRentalManagementService
{
    public function __construct(
        private OrderItemRepositoryPort $orderItems,
        private AdminRentalFormatter $formatter,
        private UnitOfWork $persistence,
    ) {
    }

    /**
     * @return array{
     *   items:list<array<string,mixed>>,
     *   total:int
     * }
     */
    public function list(
        ?string $search,
        ?string $timeline,
        ?string $requestStatus,
        ?string $requestType,
        int $limit,
        int $offset,
    ): array {
        $today = new \DateTimeImmutable('today');
        $items = $this->orderItems->findRentalsForAdmin($search, $timeline, $requestStatus, $requestType, $today, $limit, $offset);

        return [
            'items' => array_map(fn (OrderItem $item): array => $this->formatter->format($item, $today), $items),
            'total' => $this->orderItems->countRentalsForAdmin($search, $timeline, $requestStatus, $requestType, $today),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function show(int $id): ?array
    {
        $item = $this->orderItems->findAdminRentalById($id);

        return $item instanceof OrderItem
            ? $this->formatter->format($item, new \DateTimeImmutable('today'))
            : null;
    }

    /**
     * @return array{rental:array<string,mixed>,message:string}|null
     */
    public function handleAction(int $id, string $action): ?array
    {
        $item = $this->orderItems->findAdminRentalById($id);
        if (!$item instanceof OrderItem) {
            return null;
        }

        $today = new \DateTimeImmutable('today');

        return match ($action) {
            'approve_extension' => [
                'rental' => $this->formatter->format($this->approveExtension($item), $today),
                'message' => 'La prolongation a été appliquée.',
            ],
            'approve_end_early' => [
                'rental' => $this->formatter->format($this->approveEndEarly($item), $today),
                'message' => 'La fin anticipée a été appliquée.',
            ],
            'reject_request' => [
                'rental' => $this->formatter->format($this->rejectRequest($item), $today),
                'message' => 'La demande a été rejetée.',
            ],
            'mark_returned' => [
                'rental' => $this->formatter->format($this->markReturned($item), $today),
                'message' => 'Le matériel a été marqué comme récupéré.',
            ],
            default => throw new \InvalidArgumentException('Action de gestion de location invalide.'),
        };
    }

    private function approveExtension(OrderItem $item): OrderItem
    {
        $this->assertPendingRequest($item, 'extend');
        $requestedEndDate = $item->getRentalRequestedEndDate();
        $startDate = $item->getRentalStartDate();
        if (null === $requestedEndDate || null === $startDate) {
            throw new \InvalidArgumentException('La prolongation demandée est incomplète.');
        }

        $alignedMonths = RentalPeriodCalculator::findAlignedMonthsForEndDate($startDate, $requestedEndDate);
        if (null === $alignedMonths) {
            throw new \InvalidArgumentException('La date demandée ne correspond pas à une échéance de location valide.');
        }

        $item->applyApprovedRentalExtension($requestedEndDate, $alignedMonths);
        $this->recalculateRentalLineTotals($item, $alignedMonths);
        $this->recalculateOrderTotals($item->getOrder());
        $this->flushItemAndOrder($item);

        return $item;
    }

    private function approveEndEarly(OrderItem $item): OrderItem
    {
        $this->assertPendingRequest($item, 'end_early');
        $requestedEndDate = $item->getRentalRequestedEndDate();
        if (null === $requestedEndDate) {
            throw new \InvalidArgumentException('La fin anticipée demandée est incomplète.');
        }

        $alignedMonths = RentalPeriodCalculator::findAlignedMonthsForEndDate($item->getRentalStartDate(), $requestedEndDate);
        $item->applyApprovedRentalEarlyEnd($requestedEndDate, $alignedMonths);
        $this->flushItemAndOrder($item);

        return $item;
    }

    private function rejectRequest(OrderItem $item): OrderItem
    {
        if ('pending' !== $item->getRentalRequestStatus()) {
            throw new \InvalidArgumentException('Aucune demande en attente à rejeter sur cette location.');
        }

        $item->clearRentalRequest();
        $this->flushItemAndOrder($item);

        return $item;
    }

    private function markReturned(OrderItem $item): OrderItem
    {
        if ('scheduled' !== $item->getRentalReturnStatus()) {
            throw new \InvalidArgumentException('Aucune restitution planifiée à clôturer sur cette location.');
        }

        $item->markRentalReturned();
        $this->flushItemAndOrder($item);

        return $item;
    }

    private function assertPendingRequest(OrderItem $item, string $expectedType): void
    {
        if ('pending' !== $item->getRentalRequestStatus()) {
            throw new \InvalidArgumentException('Aucune demande en attente sur cette location.');
        }

        if ($item->getRentalRequestType() !== $expectedType) {
            throw new \InvalidArgumentException('Le type de demande en attente ne correspond pas à l’action choisie.');
        }
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

    private function recalculateOrderTotals(?Order $order): void
    {
        if (!$order instanceof Order) {
            throw new \LogicException('La commande associée à cette location est introuvable.');
        }

        $subtotal = 0;
        foreach ($order->getItems() as $line) {
            $subtotal += $line->getLinePriceCents();
        }

        $discount = max(0, $order->getDiscountAmountCents());
        $order->replacePaymentAmounts($subtotal, $discount, max(0, $subtotal - $discount));
    }

    private function flushItemAndOrder(OrderItem $item): void
    {
        $this->persistence->persist($item);
        if ($item->getOrder() instanceof Order) {
            $this->persistence->persist($item->getOrder());
        }
        $this->persistence->flush();
    }
}
