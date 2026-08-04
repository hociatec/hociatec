<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Persistence;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TradeInPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(TradeInRequest $request): void
    {
        $this->entityManager->persist($request);
    }

    public function remove(TradeInRequest $request): void
    {
        $this->entityManager->remove($request);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
