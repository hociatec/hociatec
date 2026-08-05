<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Handler;

use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Module\Quote\Application\Port\ServiceOfferingRepositoryPort;

final readonly class DeleteQuoteServiceHandler
{
    public function __construct(
        private ServiceOfferingRepositoryPort $services,
        private QuotePersistence $persistence,
    ) {
    }

    public function delete(ServiceOffering $service): void
    {
        $this->services->delete($service);
        $this->persistence->commit();
    }
}
