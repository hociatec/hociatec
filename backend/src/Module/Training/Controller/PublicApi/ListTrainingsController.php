<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\PublicApi;

use App\Module\Training\Repository\TrainingRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trainings', name: 'api_public_trainings_list', methods: ['GET'])]
#[RateLimiter('public_api')]
class ListTrainingsController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepository $trainings,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $category = trim((string) $request->query->get('category', ''));

        return ApiResponse::success([
            'items' => array_map(fn ($training) => $this->formatter->formatTraining($training), $this->trainings->findActive($category !== '' ? $category : null)),
        ]);
    }
}
