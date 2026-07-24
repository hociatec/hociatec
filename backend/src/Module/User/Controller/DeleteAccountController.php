<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Shared\Http\ApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/profile', name: 'api_auth_profile_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class DeleteAccountController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->userRepository->remove($user, true);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to delete user account.', [
                'userId' => $user->getId(),
                'exception' => $exception,
            ]);

            return ApiResponse::error(
                'Impossible de supprimer le compte pour le moment.',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::success([
            'message' => 'Compte supprime avec succes.',
        ]);
    }
}
