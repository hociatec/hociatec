<?php

declare(strict_types=1);

namespace App\Module\Audit\Controller\Client;

use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits/{id}', name: 'api_audits_show_mine', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ShowMyAuditController extends AbstractController
{
    public function __construct(private readonly AuditRequestRepository $repository) {}

    public function __invoke(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $audit = $this->repository->find($id);
        if ($audit === null || $audit->getClient()->getId() !== $user->getId()) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success([
            'id' => $audit->getId(),
            'number' => $audit->getNumber(),
            'type' => $audit->getType()->value,
            'status' => $audit->getStatus(),
            'url' => $audit->getTargetUrl(),
            'objectives' => $audit->getObjectives(),
            'createdAt' => $audit->getCreatedAt()->format(DATE_ATOM),
            'items' => array_map(static function($it) {
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
        ]);
    }
}
