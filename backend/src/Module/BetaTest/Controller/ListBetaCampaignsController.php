<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Repository\BetaCampaignRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/campaigns', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class ListBetaCampaignsController extends AbstractController
{
    public function __construct(private readonly BetaCampaignRepository $campaigns)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['items' => array_map(static fn ($campaign) => ['id' => $campaign->getId(), 'name' => $campaign->getName(), 'description' => $campaign->getDescription(), 'status' => $campaign->getStatus(), 'startsAt' => $campaign->getStartsAt()?->format(DATE_ATOM), 'endsAt' => $campaign->getEndsAt()?->format(DATE_ATOM)], $this->campaigns->findBy(['status' => 'active'], ['startsAt' => 'ASC']))]);
    }
}
