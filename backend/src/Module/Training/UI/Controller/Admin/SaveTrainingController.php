<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Admin;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Training\Application\DTO\TrainingInput;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Service\TrainingWriter;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Infrastructure\Repository\TrainingRepository;
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
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request, ?int $id = null): JsonResponse
    {
        $payload = \App\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = TrainingInput::fromArray($payload);
        $this->validator->validate($input);

        $training = null !== $id ? $this->trainings->find($id) : null;
        if (null !== $id && null === $training) {
            return ApiResponse::error('Formation introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (null === $training) {
            $training = new Training($input->title, $this->writer->slugify($input->slug ?? $input->title), $input->durationMinutes, $input->priceCents);
        }

        $this->writer->apply($training, $input);
        $this->writer->save($training);

        return ApiResponse::success(
            $this->formatter->formatTraining($training),
            null === $id ? Response::HTTP_CREATED : Response::HTTP_OK,
            null === $id ? 'La formation a bien été créée.' : 'La formation a bien été mise à jour.',
        );
    }
}
