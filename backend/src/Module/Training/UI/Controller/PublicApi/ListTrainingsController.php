<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\PublicApi;

use App\Module\Training\Application\Port\TrainingRepositoryPort;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Shared\Domain\ValueObject\DecimalNumber;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trainings', name: 'api_public_trainings_list', methods: ['GET'])]
#[RateLimited('public_api')]
class ListTrainingsController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepositoryPort $trainings,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $search = RequestQueryMapper::nullableString($request, 'q');
        $category = RequestQueryMapper::nullableString($request, 'category');
        $format = RequestQueryMapper::choice($request, 'format', ['onsite', 'remote']);
        $sort = RequestQueryMapper::choice($request, 'sort', [
            'title_asc',
            'price_asc',
            'price_desc',
            'duration_asc',
            'duration_desc',
        ], 'title_asc') ?? 'title_asc';
        $minPriceCents = $this->toPriceCents($request, 'minPrice');
        $maxPriceCents = $this->toPriceCents($request, 'maxPrice');
        $minDuration = RequestQueryMapper::intOrNull($request, 'minDuration');
        $maxDuration = RequestQueryMapper::intOrNull($request, 'maxDuration');
        $pagination = RequestQueryMapper::pagination($request, 20, 50);
        $total = $this->trainings->countPublicCatalog(
            $search,
            $category,
            $format,
            $minPriceCents,
            $maxPriceCents,
            $minDuration,
            $maxDuration,
        );

        return ApiResponse::paginated(
            array_map(
                fn ($training) => $this->formatter->formatTraining($training),
                $this->trainings->findPublicCatalog(
                    $search,
                    $category,
                    $format,
                    $minPriceCents,
                    $maxPriceCents,
                    $minDuration,
                    $maxDuration,
                    $sort,
                    $pagination->perPage,
                    $pagination->offset(),
                ),
            ),
            $pagination->metadata($total),
        );
    }

    private function toPriceCents(Request $request, string $name): ?int
    {
        $value = RequestQueryMapper::nullableString($request, $name);

        if (null === $value) {
            return null;
        }

        $cents = DecimalNumber::toScaledInt($value, 2);

        return null === $cents ? null : max(0, $cents);
    }
}
