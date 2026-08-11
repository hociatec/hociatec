<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Workflow\CustomerBugReportPortalService;
use App\Module\BetaTest\UI\Http\BugReportResponseFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}', name: 'api_beta_reports_show', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ShowBugReportController extends AbstractController
{
    public function __construct(
        private readonly CustomerBugReportPortalService $portal,
        private readonly BugReportResponseFormatter $formatter,
    )
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        try {
            $report = $this->portal->showForUser($user, $id);
        } catch (\DomainException $exception) {
            return ApiResponse::error('Accès refusé.', 403);
        }
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        return ApiResponse::successItem('report', $this->formatter->format($report));
    }
}
