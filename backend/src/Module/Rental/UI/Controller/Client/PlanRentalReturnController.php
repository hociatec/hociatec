<?php

declare(strict_types=1);

namespace App\Module\Rental\UI\Controller\Client;

use App\Module\Rental\Application\DTO\PlanRentalReturnInput;
use App\Module\Rental\Application\Workflow\CustomerRentalPortalService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/rentals/{id}/return-plan', name: 'api_rentals_plan_return', methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
final class PlanRentalReturnController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerRentalPortalService $portal,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload JSON invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = PlanRentalReturnInput::fromArray($payload);
        $this->validator->validate($input);

        try {
            $result = $this->portal->scheduleReturnForUser($this->currentUser(), $id, $input->mode, $input->requestedDate);
        } catch (\DomainException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Restitution impossible.', Response::HTTP_FORBIDDEN);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Planification de restitution invalide.', Response::HTTP_BAD_REQUEST);
        }

        if (null === $result) {
            return ApiResponse::error('Location introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($result);
    }
}
