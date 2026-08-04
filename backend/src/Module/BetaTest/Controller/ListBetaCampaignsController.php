<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\BetaTest\Service\BetaCampaignProvider;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/campaigns', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class ListBetaCampaignsController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepository $profiles,
        private readonly BetaCampaignProvider $campaigns,
    )
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $profile = $this->profiles->findOneByUser($user);
        if (null === $profile || BetaTesterProfile::STATUS_ACCEPTED !== $profile->getStatus()) {
            return ApiResponse::success(['items' => []]);
        }

        $now = new \DateTimeImmutable();
        $campaigns = $this->campaigns->openCampaigns();

        return ApiResponse::success(['items' => array_map(static fn ($campaign) => ['id' => $campaign->getId(), 'name' => $campaign->getName(), 'description' => $campaign->getDescription(), 'status' => $campaign->getEffectiveStatus($now), 'startsAt' => $campaign->getStartsAt()?->format(DATE_ATOM), 'endsAt' => $campaign->getEndsAt()?->format(DATE_ATOM)], $campaigns)]);
    }
}
