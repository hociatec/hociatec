<?php

declare(strict_types=1);

namespace App\Module\Audit\Service;

use App\Module\Audit\Entity\AuditEvent;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuditEventLogger
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function log(AuditRequest $audit, ?User $actor, string $type, ?string $message = null): void
    {
        $event = new AuditEvent(
            $audit,
            $type,
            $message,
            $actor?->getId(),
            $actor?->getFullName() ?? $actor?->getEmail()
        );
        $this->em->persist($event);
        $this->em->flush();
    }
}

