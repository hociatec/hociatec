<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\Quote\Domain\Entity\Service;

final readonly class QuoteCatalogManager
{
    public function __construct(private DoctrinePersistence $persistence)
    {
    }

    public function delete(Service $service): void
    {
        $this->persistence->remove($service);
        $this->persistence->flush();
    }
}
