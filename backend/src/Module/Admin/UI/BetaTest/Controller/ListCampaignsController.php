<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\CloseElapsedBetaCampaignsHandler;
use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\BetaTest\Domain\Enum\BetaTesterStatus;
use App\Module\BetaTest\UI\Http\BugReportResponseFormatter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns', methods: ['GET'])] #[IsGranted('ROLE_BETA_MANAGER')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepositoryPort $campaigns,
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly BugReportRepositoryPort $reports,
        private readonly BugReportResponseFormatter $reportFormatter,
        private readonly CloseElapsedBetaCampaignsHandler $closeElapsedCampaigns,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $now = new \DateTimeImmutable();
        $campaigns = $this->campaigns->findBy([], ['createdAt' => 'DESC'], $pagination->perPage, $pagination->offset());
        $this->closeElapsedCampaigns->closeElapsed($campaigns, $now);
        $total = $this->campaigns->count([]);

        $acceptedProfilesCount = $this->profiles->count(['status' => BetaTesterStatus::ACCEPTED->value]);

        return ApiResponse::paginated(array_map(function ($c) use ($now, $acceptedProfilesCount) {
            $reports = $this->reports->findBy(['campaign' => $c], ['createdAt' => 'DESC'], 20);

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
        }, $campaigns), $pagination->metadata($total));
    }
}
