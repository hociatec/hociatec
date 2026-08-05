<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Persistence;

use App\Module\Audit\Application\Port\AuditPersistencePort;
use App\Module\Audit\Domain\Entity\AuditRequest;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AuditPersistence implements AuditPersistencePort
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AuditRequest $audit): void
    {
        $this->entityManager->persist($audit);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
