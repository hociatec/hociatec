<?php

declare(strict_types=1);

namespace App\Module\Order\UI\Controller;

use App\Module\Order\Application\Workflow\CustomerOrderPortalService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/{orderId}/cancel', name: 'api_orders_cancel', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CancelMyOrderController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerOrderPortalService $portal,
    ) {
    }

    public function __invoke(int $orderId): JsonResponse
    {
        try {
            $order = $this->portal->cancelForUser($this->currentUser(), $orderId);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Annulation de commande impossible.', Response::HTTP_BAD_REQUEST);
        }
        if (null === $order) {
            return ApiResponse::error('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::successItem('order', $order);
    }
}
