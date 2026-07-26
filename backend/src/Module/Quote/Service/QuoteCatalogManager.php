<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QuoteCatalogManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function delete(Service $service): void
    {
        $this->entityManager->remove($service);
        $this->entityManager->flush();
    }
}
