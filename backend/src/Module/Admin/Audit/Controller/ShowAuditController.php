<?php

declare(strict_types=1);

namespace App\Module\Admin\Audit\Controller;

use App\Module\Audit\Repository\AuditEventRepository;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/audits/{id}', name: 'api_admin_audits_show', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ShowAuditController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $repository,
        private readonly AuditEventRepository $events,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $audit = $this->repository->find($id);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $events = $this->events->findByAudit($audit, 'DESC');

        return ApiResponse::success([
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
            'type' => $audit->getType()->value,
            'status' => $audit->getStatus(),
            'url' => $audit->getTargetUrl(),
            'objectives' => $audit->getObjectives(),
            'client' => [
                'id' => $audit->getClient()->getId(),
                'name' => $audit->getClient()->getFullName(),
                'email' => $audit->getClient()->getEmail(),
            ],
            'items' => array_map(static function ($it) {
                return [
                    'id' => $it->getId(),
                    'category' => $it->getCategory(),
                    'key' => $it->getCriterionKey(),
                    'label' => $it->getLabel(),
                    'position' => $it->getPosition(),
                    'level' => $it->getLevel(),
                    'isCompliant' => $it->getIsCompliant(),
                    'comment' => $it->getComment(),
                ];
            }, $audit->getItems()->toArray()),
            'events' => array_map(static function ($e) {
                return [
                    'id' => $e->getId(),
                    'type' => $e->getType(),
                    'message' => $e->getMessage(),
                    'actor' => [
                        'id' => $e->getActorUserId(),
                        'name' => $e->getActorName(),
                    ],
                    'createdAt' => $e->getCreatedAt()->format(DATE_ATOM),
                ];
            }, $events),
        ]);
    }
}
