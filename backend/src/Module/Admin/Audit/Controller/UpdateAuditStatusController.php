<?php

declare(strict_types=1);

namespace App\Module\Admin\Audit\Controller;

use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\Audit\Service\AuditEventLogger;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/audits/{id}/status', name: 'api_admin_audits_update_status', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateAuditStatusController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $repository,
        private readonly AuditEventLogger $events,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $audit = $this->repository->find($id);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }
        $payload = $request->toArray();
        $status = (string) ($payload['status'] ?? '');

        $allowed = [
            AuditRequest::STATUS_NEW,
            AuditRequest::STATUS_IN_PROGRESS,
            AuditRequest::STATUS_REVIEW,
            AuditRequest::STATUS_DONE,
        ];
        if (!in_array($status, $allowed, true)) {
            return ApiResponse::error('Statut invalide.');
        }

        $old = $audit->getStatus();
        $audit->setStatus($status);
        $this->events->save($audit);

        // Log event
        /** @var \App\Module\User\Entity\User|null $actor */
        $actor = $this->getUser();
        $this->events->log($audit, $actor, 'status_changed', sprintf('Statut: %s → %s', $old, $status));

        return ApiResponse::success(['status' => $audit->getStatus()]);
    }
}
