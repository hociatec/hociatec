<?php

declare(strict_types=1);

namespace App\Module\Training\UI\Controller\Client;

use App\Module\Training\Application\Workflow\CustomerTrainingPortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
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
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerTrainingPortalService $portal,
    ) {
    }

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        $result = $this->portal->listEnrollmentsForUser($this->currentUser(), $pagination->perPage, $pagination->offset());

        return ApiResponse::paginated(
            $result['items'],
            $pagination->metadata($result['total']),
        );
    }
}
