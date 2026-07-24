<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Entity\TrainingEnrollment;
use App\Module\Training\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/training-enrollments/{id}/status', name: 'api_admin_training_enrollments_status', methods: ['PATCH'])]
#[IsGranted('ROLE_ADMIN')]
class UpdateTrainingEnrollmentStatusController extends AbstractController
{
    private const ALLOWED = [
        TrainingEnrollment::STATUS_PENDING_PAYMENT,
        TrainingEnrollment::STATUS_PAID,
        TrainingEnrollment::STATUS_CONFIRMED,
        TrainingEnrollment::STATUS_COMPLETED,
        TrainingEnrollment::STATUS_CANCELLED,
    ];

    public function __construct(
        private readonly TrainingEnrollmentRepository $enrollments,
        private readonly TrainingFormatter $formatter,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $enrollment = $this->enrollments->find($id);
        if (null === $enrollment) {
            return ApiResponse::error('Inscription introuvable.', Response::HTTP_NOT_FOUND);
        }

        $payload = $request->toArray();
        $status = (string) ($payload['status'] ?? '');
        if (!in_array($status, self::ALLOWED, true)) {
            return ApiResponse::error('Statut invalide.', Response::HTTP_BAD_REQUEST);
        }

        $enrollment->setStatus($status);
        if (TrainingEnrollment::STATUS_PAID === $status && null === $enrollment->getPaidAt()) {
            $enrollment->setPaidAt(new \DateTimeImmutable());
        }
        $this->em->flush();

        return ApiResponse::success($this->formatter->formatEnrollment($enrollment));
    }
}
