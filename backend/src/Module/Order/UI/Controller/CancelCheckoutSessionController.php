<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Workflow\CustomerOrderPortalService;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/checkout/sessions/{stripeSessionId}/cancel', name: 'api_orders_checkout_session_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class CancelCheckoutSessionController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerOrderPortalService $portal,
    ) {
    }

    public function __invoke(string $stripeSessionId): JsonResponse
    {
        $status = $this->portal->cancelCheckoutSessionForUser($this->currentUser(), $stripeSessionId);
        if (null === $status) {
            return ApiResponse::error('Session de paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success($status);
    }
}
