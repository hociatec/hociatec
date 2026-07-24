<?php

declare(strict_types=1);

namespace App\Module\Admin\Audit\Controller;

use App\Module\Audit\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\Audit\Service\AuditEventLogger;
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
        private readonly AuditEventLogger $events,
    ) {
    }

    public function __invoke(int $auditId, int $itemId, Request $request): JsonResponse
    {
        $audit = $this->audits->find($auditId);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }
        $item = $this->items->find($itemId);
        if (null === $item) {
            return ApiResponse::error('Critère introuvable.', Response::HTTP_NOT_FOUND);
        }
        if ($item->getAudit()?->getId() !== $audit->getId()) {
            return ApiResponse::error('Association invalide.', Response::HTTP_BAD_REQUEST);
        }

        $payload = $request->toArray();
        $isCompliant = $payload['isCompliant'] ?? null;
        $comment = $payload['comment'] ?? null;

        if (null !== $isCompliant && !is_bool($isCompliant)) {
            return ApiResponse::error('Valeur de conformité invalide.');
        }
        $changes = [];
        if (null !== $isCompliant && $item->getIsCompliant() !== $isCompliant) {
            $changes[] = sprintf('Conformité: %s → %s',
                null === $item->getIsCompliant() ? 'n/a' : ($item->getIsCompliant() ? 'oui' : 'non'),
                $isCompliant ? 'oui' : 'non'
            );
            $item->setIsCompliant($isCompliant);
        }
        if (null !== $comment && $item->getComment() !== (string) $comment) {
            $changes[] = 'Commentaire mis à jour';
            $item->setComment((string) $comment);
        }
        $this->em->flush();

        if ([] !== $changes) {
            /** @var \App\Module\User\Entity\User|null $actor */
            $actor = $this->getUser();
            $this->events->log(
                $audit,
                $actor,
                'item_updated',
                sprintf('[%s] %s — %s', $item->getCategory(), $item->getLabel(), implode('; ', $changes))
            );
        }

        return ApiResponse::success([
            'id' => $item->getId(),
            'isCompliant' => $item->getIsCompliant(),
            'comment' => $item->getComment(),
        ]);
    }
}
