<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller;

use App\Module\User\Application\Exception\DeleteAccountBlockedException;
use App\Module\User\Application\Workflow\DeleteAccountService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
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
        private readonly DeleteAccountService $deleter,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->deleter->delete($user);
        } catch (DeleteAccountBlockedException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_CONFLICT);
        } catch (\RuntimeException $exception) {
            $this->logger->error('Unable to delete user account.', [
                'userId' => $user->getId(),
                'exception' => $exception,
            ]);

            return ApiResponse::error(
                'Impossible de supprimer le compte pour le moment.',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return ApiResponse::success([
            'message' => 'Compte supprime avec succes.',
        ]);
    }
}
