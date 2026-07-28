<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Service\BetaProfileChoices;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/beta/profile-options', methods: ['GET'])]
final class GetBetaProfileOptionsController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(['choices' => BetaProfileChoices::groups()]);
    }
}
