<?php

declare(strict_types=1);

namespace App\Module\Rental\Application\Workflow;

use App\Module\Order\Application\Support\RentalPeriodCalculator;
use App\Module\Order\Domain\Entity\OrderItem;

final class CustomerRentalTerminationService
{
    public function terminate(
        OrderItem $item,
        ?string $requestedEndDate,
        string $returnMode,
        ?string $returnRequestedDate,
        \DateTimeImmutable $today,
    ): void {
        $currentEndDate = $item->getRentalEndDate();
        $currentStartDate = $item->getRentalStartDate();
        if (null === $currentEndDate || null === $currentStartDate) {
            throw new \InvalidArgumentException('Cette location est incomplète et ne peut pas être clôturée.');
        }

        [$terminationDate, $returnDate] = $this->validateDates(
            $requestedEndDate,
            $returnRequestedDate,
            $currentStartDate,
            $currentEndDate,
            $today,
        );

        if ($terminationDate < $currentEndDate) {
            $item->requestRentalChange('end_early', $terminationDate);
        } elseif ('end_early' === $item->getRentalRequestType()) {
            $item->clearRentalRequest();
        }

        $item->scheduleRentalReturn($returnMode, $returnDate);
    }

    /** @return array{0:\DateTimeImmutable,1:\DateTimeImmutable} */
    private function validateDates(
        ?string $requestedEndDate,
        ?string $returnRequestedDate,
        \DateTimeImmutable $currentStartDate,
        \DateTimeImmutable $currentEndDate,
        \DateTimeImmutable $today,
    ): array {
        $terminationDate = RentalPeriodCalculator::parseDate($requestedEndDate);
        if (null === $terminationDate) {
            throw new \InvalidArgumentException('La date de fin demandée est invalide.');
        }

        $returnDate = RentalPeriodCalculator::parseDate($returnRequestedDate);
        if (null === $returnDate) {
            throw new \InvalidArgumentException('La date de restitution demandée est invalide.');
        }

        if ($terminationDate < $today) {
            throw new \InvalidArgumentException('La date de fin demandée doit être aujourd\'hui ou dans le futur.');
        }
        if ($returnDate < $today) {
            throw new \InvalidArgumentException('La date de restitution doit être aujourd\'hui ou dans le futur.');
        }
        if ($terminationDate < $currentStartDate || $terminationDate > $currentEndDate) {
            throw new \InvalidArgumentException('Choisissez une date de fin comprise dans la période actuelle de location.');
        }
        if ($returnDate < $currentStartDate || $returnDate > $terminationDate) {
            throw new \InvalidArgumentException('La restitution doit être planifiée entre le début de location et la date de fin choisie.');
        }

        return [$terminationDate, $returnDate];
    }
}
