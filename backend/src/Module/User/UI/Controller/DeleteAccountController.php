<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller;

use App\Module\User\Application\Exception\DeleteAccountBlockedException;
use App\Module\User\Application\Workflow\DeleteAccountService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/profile', name: 'api_auth_profile_delete', methods: ['DELETE'])]
#[IsGranted('ROLE_USER')]
class DeleteAccountController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly DeleteAccountService $deleter,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->currentUser();

        try {
            $this->deleter->delete($user);
        } catch (DeleteAccountBlockedException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Suppression du compte impossible.', JsonResponse::HTTP_CONFLICT);
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
