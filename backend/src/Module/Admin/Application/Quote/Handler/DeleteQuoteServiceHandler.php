<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Handler;

use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteQuoteServiceHandler
{
    public function __construct(
        private ServiceOfferingRepositoryPort $services,
        private UnitOfWork $persistence,
    ) {
    }

    public function delete(ServiceOffering $service): void
    {
        $this->services->delete($service);
        $this->persistence->flush();
    }
}
