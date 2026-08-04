<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\Admin\BetaTest\Service\AdminBetaTesterManager;
use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers/{id}', methods: ['PATCH'])] #[IsGranted('ROLE_ADMIN')]
final class UpdateBetaTesterController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepository $profiles,
        private readonly AdminBetaTesterManager $testerManager,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $profile = $this->profiles->find($id);
        if (!$profile instanceof BetaTesterProfile) {
            return ApiResponse::error('Profil introuvable.', 404);
        }

        $status = (string) (JsonPayload::decode($request)['status'] ?? '');
        try {
            $this->testerManager->updateStatus($profile, $status);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success([], 200, 'Profil mis à jour.');
    }
}
