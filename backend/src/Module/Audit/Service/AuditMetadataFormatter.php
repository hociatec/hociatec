<?php

declare(strict_types=1);

namespace App\Module\Audit\Service;

use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Entity\AuditType;

final class AuditMetadataFormatter
{
    /** @return list<array{value: string, label: string}> */
    public function types(): array
    {
        return array_map(
            fn (AuditType $type): array => [
                'value' => $type->value,
                'label' => $this->typeLabel($type),
            ],
            AuditType::cases(),
        );
    }

    /** @return list<array{value: string, label: string}> */
    public function statuses(): array
    {
        return array_map(
            fn (string $status): array => [
                'value' => $status,
                'label' => $this->statusLabel($status),
            ],
            [
                AuditRequest::STATUS_NEW,
                AuditRequest::STATUS_IN_PROGRESS,
                AuditRequest::STATUS_REVIEW,
                AuditRequest::STATUS_DONE,
            ],
        );
    }

    public function typeLabel(AuditType $type): string
    {
        return match ($type) {
            AuditType::PERFORMANCE => 'Performance',
            AuditType::SECURITY => 'Sécurité',
            AuditType::UX => 'Expérience utilisateur (UX)',
            AuditType::SEO => 'SEO',
            AuditType::TECHNICAL => 'Technique complet',
            AuditType::ACCESSIBILITY => 'Accessibilité numérique',
        };
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            AuditRequest::STATUS_NEW => 'Non commencé',
            AuditRequest::STATUS_IN_PROGRESS => 'En cours',
            AuditRequest::STATUS_REVIEW => 'En revue',
            AuditRequest::STATUS_DONE => 'Finalisé',
            default => $status,
        };
    }
}
