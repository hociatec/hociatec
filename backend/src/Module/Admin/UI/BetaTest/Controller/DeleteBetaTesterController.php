<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\DeleteBetaTesterHandler;
use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers/{id}', methods: ['DELETE'])] #[IsGranted('ROLE_BETA_MANAGER')]
final class DeleteBetaTesterController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly DeleteBetaTesterHandler $deleteTester,
    ) {
    }

    public function __invoke(int $id): JsonResponse
    {
        $profile = $this->profiles->find($id);
        if (null === $profile) {
            return ApiResponse::error('Profil introuvable.', 404);
        }

        $this->deleteTester->delete($profile);

        return ApiResponse::success([], 200, 'Profil supprimé.');
    }
}
