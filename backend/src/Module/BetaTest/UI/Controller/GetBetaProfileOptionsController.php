<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\BetaTest\Application\Service\BetaProfileChoices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/beta/profile-options', methods: ['GET'])]
final class GetBetaProfileOptionsController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::successItem('choices', BetaProfileChoices::groups());
    }
}
