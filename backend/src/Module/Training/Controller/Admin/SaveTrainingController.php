<?php

declare(strict_types=1);

namespace App\Module\Training\Controller\Admin;

use App\Module\Training\Entity\Training;
use App\Module\Training\Repository\TrainingRepository;
use App\Module\Training\Service\TrainingFormatter;
use App\Module\Training\Service\TrainingWriter;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/trainings/{id}', name: 'api_admin_trainings_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[Route('/api/admin/trainings', name: 'api_admin_trainings_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class SaveTrainingController extends AbstractController
{
    public function __construct(
        private readonly TrainingRepository $trainings,
        private readonly TrainingWriter $writer,
        private readonly TrainingFormatter $formatter,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(Request $request, ?int $id = null): JsonResponse
    {
        $payload = (array) json_decode($request->getContent(), true);
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return ApiResponse::error('Le titre est requis.', Response::HTTP_BAD_REQUEST);
        }

        $training = $id !== null ? $this->trainings->find($id) : null;
        if ($id !== null && $training === null) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($training === null) {
            $training = new Training($title, $this->writer->slugify((string) ($payload['slug'] ?? $title)), 60, 0);
            $this->em->persist($training);
        }

        $this->writer->apply($training, $payload);
        $this->em->flush();

        return ApiResponse::success($this->formatter->formatTraining($training), $id === null ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
