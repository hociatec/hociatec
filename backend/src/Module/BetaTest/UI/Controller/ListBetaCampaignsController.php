<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Application\Provider\BetaCampaignProvider;
use App\Module\BetaTest\Domain\Enum\BetaTesterStatus;
use App\Module\BetaTest\UI\Http\BetaCampaignResponseFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/campaigns', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class ListBetaCampaignsController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly BetaCampaignProvider $campaigns,
        private readonly BetaCampaignResponseFormatter $formatter,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $profile = $this->profiles->findOneByUser($user);
        if (null === $profile || BetaTesterStatus::ACCEPTED->value !== $profile->getStatus()) {
            return ApiResponse::paginated([], $pagination->metadata(0));
        }

        $now = new \DateTimeImmutable();
        $campaigns = $this->campaigns->openCampaigns($pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($campaign) => $this->formatter->format($campaign, $now), $campaigns),
            $pagination->metadata($this->campaigns->countOpenCampaigns()),
        );
    }
}
