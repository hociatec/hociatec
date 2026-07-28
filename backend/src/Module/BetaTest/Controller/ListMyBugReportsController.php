<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Http\BugReportResponseFormatter;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class ListMyBugReportsController extends AbstractController
{
    public function __construct(private readonly BugReportRepository $reports, private readonly BugReportResponseFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        return ApiResponse::success(['items' => array_map(fn ($report) => $this->formatter->format($report), $this->reports->findForUser($user))]);
    }
}
