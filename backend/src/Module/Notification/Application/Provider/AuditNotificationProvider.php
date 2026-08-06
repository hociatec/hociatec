<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Provider;

use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Notification\Application\Notification\ComputedAccountNotificationProviderInterface;
use App\Module\Notification\Application\Projection\AccountNotificationFormatter;
use App\Module\User\Domain\Entity\User;

final readonly class AuditNotificationProvider implements ComputedAccountNotificationProviderInterface
{
    public function __construct(
        private AuditRequestRepositoryPort $audits,
        private AuditMetadataFormatter $auditMetadata,
        private AccountNotificationFormatter $formatter,
    ) {
    }

    public function provide(User $user, \DateTimeImmutable $now): array
    {
        $activeAudit = $this->activeAudit($user);
        if (null === $activeAudit) {
            return [];
        }

        $statusLabel = $this->auditMetadata->statusLabel($activeAudit->getStatus());

        return [
            $this->formatter->computedNotification(
                'audit:'.$activeAudit->getId().':'.$activeAudit->getStatus(),
                'Audit '.$statusLabel.' en suivi',
                'Votre audit '.$activeAudit->getNumber().' est actuellement à l’état : '.$statusLabel.'.',
                '/audits/me/'.$activeAudit->getId(),
                'audit_follow_up',
                $now,
            ),
        ];
    }

    private function activeAudit(User $user): ?AuditRequest
    {
        $audits = $this->audits->findByUser($user);
        foreach ($audits as $audit) {
            if (AuditRequest::STATUS_DONE !== $audit->getStatus()) {
                return $audit;
            }
        }

        return $audits[0] ?? null;
    }
}
