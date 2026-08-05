<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Audit\Controller;

use App\Module\Admin\Application\Audit\DTO\AuditStatusInput;
use App\Module\Audit\Application\Workflow\AuditEventLogger;
use App\Module\Audit\Application\Port\AuditRequestRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
        private readonly AuditRequestRepositoryPort $repository,
        private readonly AuditEventLogger $events,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $audit = $this->repository->find($id);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = AuditStatusInput::fromArray($payload);
        $this->validator->validate($input);
        $status = $input->status;

        $old = $audit->getStatus();
        $audit->setStatus($status);
        $this->events->save($audit);

        // Log event
        /** @var \App\Module\User\Domain\Entity\User|null $actor */
        $actor = $this->getUser();
        $this->events->log($audit, $actor, 'status_changed', sprintf('Statut: %s → %s', $old, $status));

        return ApiResponse::successItem('status', $audit->getStatus());
    }
}
