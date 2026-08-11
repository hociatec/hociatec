<?php

declare(strict_types=1);

namespace App\Module\Quote\UI\Controller\Client;

use App\Module\Quote\Application\Workflow\CustomerQuotePortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/quotes/me/{id}/refuse', name: 'api_quotes_me_refuse', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class RefuseMyQuoteController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerQuotePortalService $portal,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        try {
            $quote = $this->portal->refuseForUser($this->currentUser(), $id);
        } catch (\DomainException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Refus du devis impossible.', Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Demande de refus invalide.', Response::HTTP_BAD_REQUEST);
        }
        if (null === $quote) {
            return ApiResponse::error('Devis introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($quote, JsonResponse::HTTP_OK, 'Le devis a bien été refusé.');
    }
}
