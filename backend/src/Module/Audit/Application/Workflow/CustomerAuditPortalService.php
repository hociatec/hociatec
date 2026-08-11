<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Workflow;

use App\Module\Audit\Application\Port\AuditEventRepositoryPort;
use App\Module\Audit\Application\Port\AuditPdfRenderer;
use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Security\AuditAccessPolicy;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerAuditPortalService
{
    public function __construct(
        private AuditRequestRepositoryPort $audits,
        private AuditEventRepositoryPort $events,
        private AuditMetadataFormatter $metadata,
        private AuditAccessPolicy $accessPolicy,
        private AuditEventLogger $eventLogger,
        private ?AuditPdfRenderer $pdf = null,
    ) {
    }

    /**
     * @return array{items:list<array<string,mixed>>, total:int}
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $items = $this->audits->findByUser($user, $limit, $offset);

        return [
            'items' => array_map(fn (AuditRequest $audit): array => $this->formatListItem($audit), $items),
            'total' => $this->audits->countByUser($user),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function showForUser(User $user, int $auditId): ?array
    {
        $audit = $this->findViewableAudit($user, $auditId);
        if (!$audit instanceof AuditRequest) {
            return null;
        }

        $events = $this->events->findByAudit($audit, 'DESC');

        return [
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
            'type' => $audit->getType()->value,
            'typeLabel' => $this->metadata->typeLabel($audit->getType()),
            'status' => $audit->getStatus(),
            'statusLabel' => $this->metadata->statusLabel($audit->getStatus()),
            'url' => $audit->getTargetUrl(),
            'objectives' => $audit->getObjectives(),
            'createdAt' => $audit->getCreatedAt()->format(DATE_ATOM),
            'items' => array_map(static function ($item): array {
                return [
                    'id' => $item->getId(),
                    'category' => $item->getCategory(),
                    'key' => $item->getCriterionKey(),
                    'label' => $item->getLabel(),
                    'position' => $item->getPosition(),
                    'level' => $item->getLevel(),
                    'isCompliant' => $item->getIsCompliant(),
                    'comment' => $item->getComment(),
                ];
            }, $audit->getItems()->toArray()),
            'events' => array_map(static function ($event): array {
                return [
                    'id' => $event->getId(),
                    'type' => $event->getType(),
                    'message' => $event->getMessage(),
                    'createdAt' => $event->getCreatedAt()->format(DATE_ATOM),
                ];
            }, $events),
        ];
    }

    /**
     * @return array{content:string,filename:string}|null
     */
    public function renderDetailedPdfForUser(User $user, int $auditId): ?array
    {
        return $this->renderPdfForUser($user, $auditId, false);
    }

    /**
     * @return array{content:string,filename:string}|null
     */
    public function renderSummaryPdfForUser(User $user, int $auditId): ?array
    {
        return $this->renderPdfForUser($user, $auditId, true);
    }

    private function findViewableAudit(User $user, int $auditId): ?AuditRequest
    {
        $audit = $this->audits->find($auditId);

        return $audit instanceof AuditRequest && $this->accessPolicy->canView($user, $audit) ? $audit : null;
    }

    /**
     * @return array<string,mixed>
     */
    private function formatListItem(AuditRequest $audit): array
    {
        return [
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
            'type' => $audit->getType()->value,
            'typeLabel' => $this->metadata->typeLabel($audit->getType()),
            'status' => $audit->getStatus(),
            'statusLabel' => $this->metadata->statusLabel($audit->getStatus()),
            'url' => $audit->getTargetUrl(),
            'createdAt' => $audit->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array{content:string,filename:string}|null
     */
    private function renderPdfForUser(User $user, int $auditId, bool $summary): ?array
    {
        if (!$this->pdf instanceof AuditPdfRenderer) {
            throw new \LogicException('Audit PDF renderer is not configured.');
        }

        $audit = $this->findViewableAudit($user, $auditId);
        if (!$audit instanceof AuditRequest) {
            return null;
        }

        $content = $summary ? $this->pdf->renderSummary($audit) : $this->pdf->renderDetailed($audit);
        $this->eventLogger->log(
            $audit,
            $user,
            'pdf_generated',
            $summary ? 'Synthèse PDF (client)' : 'Rapport détaillé (client)',
        );

        return [
            'content' => $content,
            'filename' => sprintf('%s-%s.pdf', $audit->getNumber(), $summary ? 'synthese' : 'rapport'),
        ];
    }
}
