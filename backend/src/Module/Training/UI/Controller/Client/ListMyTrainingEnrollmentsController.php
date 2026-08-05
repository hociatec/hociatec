<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Client;

use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Port\TrainingEnrollmentRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trainings/enrollments/me', name: 'api_training_enrollments_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyTrainingEnrollmentsController extends AbstractController
{
    public function __construct(
        private readonly TrainingEnrollmentRepositoryPort $enrollments,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::paginated(
            array_map(fn ($enrollment) => $this->formatter->formatEnrollment($enrollment), $this->enrollments->findForUser($user, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($this->enrollments->countForUser($user)),
        );
    }
}
