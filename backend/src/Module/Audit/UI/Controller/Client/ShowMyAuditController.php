<?php

declare(strict_types=1);

namespace App\Module\Audit\UI\Controller\Client;

use App\Module\Audit\Application\Workflow\CustomerAuditPortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/audits/{id}', name: 'api_audits_show_mine', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ShowMyAuditController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerAuditPortalService $portal,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $audit = $this->portal->showForUser($this->currentUser(), $id);
        if (null === $audit) {
            return ApiResponse::error('Audit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($audit);
    }
}
