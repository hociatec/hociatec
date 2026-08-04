<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\Pagination;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-enrollments', name: 'api_admin_training_enrollments_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListTrainingEnrollmentsController extends AbstractController
{
    public function __construct(private readonly TrainingEnrollmentRepository $enrollments, private readonly TrainingFormatter $formatter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pagination = Pagination::fromRequest($request);
        $items = $this->enrollments->findBy([], ['createdAt' => 'DESC'], $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            array_map(fn ($enrollment) => $this->formatter->formatEnrollment($enrollment), $items),
            $pagination->metadata($this->enrollments->count([])),
        );
    }
}
