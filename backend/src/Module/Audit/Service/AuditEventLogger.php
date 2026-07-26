<?php

declare(strict_types=1);

namespace App\Module\Audit\Service;

use App\Module\Audit\Entity\AuditEvent;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\User\Entity\User;
use App\Shared\Persistence\DoctrinePersistence;

class AuditEventLogger
{
    public function __construct(private readonly DoctrinePersistence $persistence)
    {
    }

    public function log(AuditRequest $audit, ?User $actor, string $type, ?string $message = null): void
    {
        $event = new AuditEvent(
            $audit,
            $type,
            $message,
            $actor?->getId(),
            $actor?->getFullName() ?? $actor?->getEmail()
        );
        $this->persistence->persist($event);
        $this->persistence->flush();
    }

    public function save(object $entity): void
    {
        $this->persistence->persist($entity);
        $this->persistence->flush();
    }
}
