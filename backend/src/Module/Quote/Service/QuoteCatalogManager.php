<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Service;
use App\Shared\Persistence\DoctrinePersistence;

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
