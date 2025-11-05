<?php

declare(strict_types=1);

namespace App\Module\Admin\Audit\Controller;

use App\Module\Audit\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/audits/{auditId}/items/{itemId}', name: 'api_admin_audits_update_item', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateChecklistItemController extends AbstractController
{
    public function __construct(
        private readonly AuditRequestRepository $audits,
        private readonly AuditChecklistItemRepository $items,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(int $auditId, int $itemId, Request $request): JsonResponse
    {
        $audit = $this->audits->find($auditId);
        if ($audit === null) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }
        $item = $this->items->find($itemId);
        if ($item === null) {
            return ApiResponse::error('Critère introuvable.', Response::HTTP_NOT_FOUND);
        }
        if ($item->getAudit()?->getId() !== $audit->getId()) {
            return ApiResponse::error('Association invalide.', Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode((string) $request->getContent(), true) ?? [];
        $isCompliant = $payload['isCompliant'] ?? null;
        $comment = $payload['comment'] ?? null;

        if ($isCompliant !== null && !is_bool($isCompliant)) {
            return ApiResponse::error('Valeur de conformité invalide.');
        }
        if ($isCompliant !== null) {
            $item->setIsCompliant($isCompliant);
        }
        $item->setComment($comment !== null ? (string) $comment : null);
        $this->em->flush();

        return ApiResponse::success([
            'id' => $item->getId(),
            'isCompliant' => $item->getIsCompliant(),
            'comment' => $item->getComment(),
        ]);
    }
}

