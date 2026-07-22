<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Client;

use App\Module\Training\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trainings/enrollments/me', name: 'api_training_enrollments_me', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListMyTrainingEnrollmentsController extends AbstractController
{
    public function __construct(
        private readonly TrainingEnrollmentRepository $enrollments,
        private readonly TrainingFormatter $formatter,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::success([
            'items' => array_map(fn ($enrollment) => $this->formatter->formatEnrollment($enrollment), $this->enrollments->findForUser($user)),
        ]);
    }
}
