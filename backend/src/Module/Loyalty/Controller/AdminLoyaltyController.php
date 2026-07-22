<?php

declare(strict_types=1);

namespace App\Module\Loyalty\Controller;

use App\Module\Loyalty\Service\LoyaltyService;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/loyalty')]
#[IsGranted('ROLE_ADMIN')]
final class AdminLoyaltyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly LoyaltyService $loyalty,
    ) {
    }

    #[Route('', name: 'api_admin_loyalty_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.loyaltyPointsBalance', 'DESC')
            ->addOrderBy('u.createdAt', 'DESC')
            ->setMaxResults(200);

        if ($search !== '') {
            $qb
                ->andWhere('LOWER(u.email) LIKE LOWER(:search) OR LOWER(u.firstName) LIKE LOWER(:search) OR LOWER(u.lastName) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $search . '%');
        }

        return ApiResponse::success([
            'items' => array_map(fn (User $user): array => $this->formatCustomer($user), $qb->getQuery()->getResult()),
        ]);
    }

    #[Route('/customers/{userId}', name: 'api_admin_loyalty_update_customer', methods: ['PATCH'])]
    public function updateCustomer(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (!$user instanceof User) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $points = (int) ($payload['points'] ?? $user->getLoyaltyPointsBalance());
        $this->loyalty->adjustBalance($user, $points);

        return ApiResponse::success(['customer' => $this->formatCustomer($user)]);
    }

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
