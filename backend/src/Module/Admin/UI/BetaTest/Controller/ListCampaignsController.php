<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\BetaTest\Service\AdminBetaCampaignManager;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Infrastructure\Http\BugReportResponseFormatter;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns', methods: ['GET'])] #[IsGranted('ROLE_ADMIN')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepository $campaigns,
        private readonly BetaTesterProfileRepository $profiles,
        private readonly BugReportRepository $reports,
        private readonly BugReportResponseFormatter $reportFormatter,
        private readonly AdminBetaCampaignManager $campaignManager,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $now = new \DateTimeImmutable();
        $campaigns = $this->campaigns->findBy([], ['createdAt' => 'DESC']);
        $this->campaignManager->closeElapsedCampaigns($campaigns, $now);

        $acceptedProfilesCount = $this->profiles->count(['status' => BetaTesterProfile::STATUS_ACCEPTED]);

        return ApiResponse::success(['items' => array_map(function ($c) use ($now, $acceptedProfilesCount) {
            $reports = $this->reports->findBy(['campaign' => $c], ['createdAt' => 'DESC']);

            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'description' => $c->getDescription(),
                'status' => $c->getEffectiveStatus($now),
                'startsAt' => $c->getStartsAt()?->format(DATE_ATOM),
                'endsAt' => $c->getEndsAt()?->format(DATE_ATOM),
                'createdAt' => $c->getCreatedAt()->format(DATE_ATOM),
                'enrolledCount' => $acceptedProfilesCount,
                'reportCount' => count($reports),
                'reports' => array_map(fn ($report) => $this->reportFormatter->format($report), $reports),
            ];
        }, $campaigns)]);
    }
}
