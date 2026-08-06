<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\BetaTest\UI\Http\BugReportResponseFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class ListMyBugReportsController extends AbstractController
{
    public function __construct(private readonly BugReportRepositoryPort $reports, private readonly BugReportResponseFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $pagination = RequestQueryMapper::pagination($request, 12, 100);

        return ApiResponse::paginated(
            array_map(fn ($report) => $this->formatter->format($report), $this->reports->findForUserPaginated($user, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($this->reports->countForUser($user)),
        );
    }
}
