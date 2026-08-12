<?php

declare(strict_types=1);

namespace App\Module\Support\UI\Controller;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Support\Application\Workflow\CustomerSupportPortalService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support/me/{id}', name: 'api_support_me_show', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class ShowMySupportRequestController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(private readonly CustomerSupportPortalService $portal)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        try {
            $item = $this->portal->showForUser($this->currentUser(), $id);
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Demande SAV introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::successItem('item', $item);
    }
}
