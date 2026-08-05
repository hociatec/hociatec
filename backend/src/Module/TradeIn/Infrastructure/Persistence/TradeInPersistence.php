<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Persistence;

use App\Module\TradeIn\Application\Port\TradeInPersistencePort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TradeInPersistence implements TradeInPersistencePort
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
