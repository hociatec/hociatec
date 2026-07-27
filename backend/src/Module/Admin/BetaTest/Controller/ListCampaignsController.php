<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Repository\BetaCampaignRepository;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-campaigns', methods: ['GET'])] #[IsGranted('ROLE_ADMIN')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(private readonly BetaCampaignRepository $campaigns)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['items' => array_map(static fn ($c) => ['id' => $c->getId(), 'name' => $c->getName(), 'description' => $c->getDescription(), 'status' => $c->getStatus(), 'startsAt' => $c->getStartsAt()?->format(DATE_ATOM), 'endsAt' => $c->getEndsAt()?->format(DATE_ATOM), 'createdAt' => $c->getCreatedAt()->format(DATE_ATOM)], $this->campaigns->findBy([], ['createdAt' => 'DESC']))]);
    }
}
