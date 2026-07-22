<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-enrollments', name: 'api_admin_training_enrollments_list', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ListTrainingEnrollmentsController extends AbstractController
{
    public function __construct(private readonly TrainingEnrollmentRepository $enrollments, private readonly TrainingFormatter $formatter)
    {
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'items' => array_map(fn ($enrollment) => $this->formatter->formatEnrollment($enrollment), $this->enrollments->findBy([], ['createdAt' => 'DESC'])),
        ]);
    }
}
