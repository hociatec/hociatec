<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Repository\TrainingRepository;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trainings/{id}', name: 'api_admin_trainings_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
class DeleteTrainingController extends AbstractController
{
    public function __construct(private readonly TrainingRepository $trainings, private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $training = $this->trainings->find($id);
        if ($training === null) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($training);
        $this->em->flush();

        return ApiResponse::success(['deleted' => true]);
    }
}
