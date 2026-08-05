<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Workflow;

use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

class AuditEventLogger
{
    public function __construct(private readonly UnitOfWork $persistence)
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
        $this->persistence->commit();
    }

    public function save(object $entity): void
    {
        $this->persistence->persist($entity);
        $this->persistence->commit();
    }
}
