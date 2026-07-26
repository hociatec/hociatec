<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Service;

use App\Module\TradeIn\Entity\TradeInRequest;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TradeInPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(TradeInRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function remove(TradeInRequest $request): void
    {
        $this->entityManager->remove($request);
        $this->entityManager->flush();
    }
}
