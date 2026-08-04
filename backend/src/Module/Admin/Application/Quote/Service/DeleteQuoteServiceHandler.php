<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\Service;

use App\Module\Quote\Application\Persistence\QuotePersistence;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\Quote\Infrastructure\Repository\ServiceRepository;

final readonly class DeleteQuoteServiceHandler
{
    public function __construct(
        private ServiceRepository $services,
        private QuotePersistence $persistence,
    ) {
    }

    public function delete(Service $service): void
    {
        $this->services->delete($service);
        $this->persistence->commit();
    }
}
