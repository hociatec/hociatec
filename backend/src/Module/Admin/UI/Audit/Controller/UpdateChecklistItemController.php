<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Audit\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Admin\Application\Audit\DTO\ChecklistItemInput;
use App\Module\Audit\Application\Service\AuditEventLogger;
use App\Module\Audit\Infrastructure\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
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
        private readonly AuditEventLogger $events,
        private readonly DtoValidator $validator,
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

        $payload = \App\Infrastructure\Http\JsonPayload::decode($request);
        $input = ChecklistItemInput::fromArray($payload);
        $this->validator->validate($input);
        $isCompliant = $input->isCompliant;
        $comment = $input->comment;
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
        $this->events->save($item);

        if ([] !== $changes) {
            /** @var \App\Module\User\Domain\Entity\User|null $actor */
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
