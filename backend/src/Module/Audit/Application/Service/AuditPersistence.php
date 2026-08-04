<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Service;

use App\Module\Audit\Domain\Entity\AuditRequest;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AuditPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(AuditRequest $audit): void
    {
        $this->entityManager->persist($audit);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
