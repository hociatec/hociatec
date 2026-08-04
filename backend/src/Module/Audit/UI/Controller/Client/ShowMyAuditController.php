<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\Domain\Security\AuditAccessPolicy;
use App\Module\Audit\Infrastructure\Repository\AuditEventRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits/{id}', name: 'api_audits_show_mine', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ShowMyAuditController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $repository,
        private readonly AuditEventRepository $events,
        private readonly AuditMetadataFormatter $metadata,
        private readonly AuditAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $audit = $this->repository->find($id);
        if (null === $audit || !$this->accessPolicy->canView($user, $audit)) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $events = $this->events->findByAudit($audit, 'DESC');

        return ApiResponse::success([
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
            'type' => $audit->getType()->value,
            'typeLabel' => $this->metadata->typeLabel($audit->getType()),
            'status' => $audit->getStatus(),
            'statusLabel' => $this->metadata->statusLabel($audit->getStatus()),
            'url' => $audit->getTargetUrl(),
            'objectives' => $audit->getObjectives(),
            'createdAt' => $audit->getCreatedAt()->format(DATE_ATOM),
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
                    'createdAt' => $e->getCreatedAt()->format(DATE_ATOM),
                ];
            }, $events),
        ]);
    }
}
