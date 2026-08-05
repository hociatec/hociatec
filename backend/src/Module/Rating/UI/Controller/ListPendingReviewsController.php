<?php

declare(strict_types=1);

namespace App\Module\Rating\UI\Controller;

use App\Module\Rating\Application\Provider\PendingReviewResolver;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public function __invoke(?Request $request = null): JsonResponse
    {
        $request ??= new Request();
        $pagination = RequestQueryMapper::pagination($request, 10, 50);
        /** @var User $user */
        $user = $this->getUser();

        return ApiResponse::paginated(
            $this->resolver->resolve($user, $pagination->perPage, $pagination->offset()),
            $pagination->metadata($this->resolver->count($user)),
        );
    }
}
