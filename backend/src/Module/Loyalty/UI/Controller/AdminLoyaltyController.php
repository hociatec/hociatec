<?php

declare(strict_types=1);

namespace App\Module\Loyalty\UI\Controller;

use App\Module\Loyalty\Application\Workflow\LoyaltyService;
use App\Module\Loyalty\Domain\Exception\LoyaltyOperationException;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/loyalty')]
#[IsGranted('ROLE_LOYALTY_MANAGER')]
final class AdminLoyaltyController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryPort $users,
        private readonly LoyaltyService $loyalty,
    ) {
    }

    #[Route('', name: 'api_admin_loyalty_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $search = RequestQueryMapper::string($request, 'q');
        $pagination = RequestQueryMapper::pagination($request, 10, 50);

        return ApiResponse::paginated(
            array_map(fn (User $user): array => $this->formatCustomer($user), $this->loyalty->findCustomers($search, $pagination->perPage, $pagination->offset())),
            $pagination->metadata($this->loyalty->countCustomers($search)),
        );
    }

    #[Route('/customers/{userId}', name: 'api_admin_loyalty_update_customer', methods: ['PATCH'])]
    public function updateCustomer(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (!$user instanceof User) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $points = (int) ($payload['points'] ?? $user->getLoyaltyPointsBalance());
        try {
            $this->loyalty->adjustBalance($user, $points);
        } catch (LoyaltyOperationException) {
            return ApiResponse::internalError();
        }

        return ApiResponse::success(['customer' => $this->formatCustomer($user)], Response::HTTP_OK, 'Le solde fidélité a bien été mis à jour.');
    }

    /** @return array<string, mixed> */
    private function formatCustomer(User $user): array
    {
        $points = $user->getLoyaltyPointsBalance();

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'points' => $points,
            'euroCents' => $this->loyalty->pointsToCents($points),
            'createdAt' => $user->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
