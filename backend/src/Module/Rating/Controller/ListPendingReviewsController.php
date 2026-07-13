<?php

declare(strict_types=1);

namespace App\Module\Rating\Controller;

use App\Module\Rating\Service\PendingReviewResolver;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders/me/pending-reviews', name: 'api_orders_pending_reviews', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ListPendingReviewsController extends AbstractController
{
    public function __construct(
        private readonly PendingReviewResolver $resolver,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::success([
            'items' => $this->resolver->resolve($user),
        ]);
    }
}
