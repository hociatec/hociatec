<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Http\BugReportResponseFormatter;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports', methods: ['GET'])] #[IsGranted('ROLE_ADMIN')]
final class ListBugReportsController extends AbstractController
{
    public function __construct(private readonly BugReportRepository $reports, private readonly BugReportResponseFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['items' => array_map(fn ($report) => $this->formatter->format($report), $this->reports->findBy([], ['createdAt' => 'DESC']))]);
    }
}
