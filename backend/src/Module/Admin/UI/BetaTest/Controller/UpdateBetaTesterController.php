<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Module\Admin\Application\BetaTest\Service\ChangeBetaTesterStatusHandler;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
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
        private readonly ChangeBetaTesterStatusHandler $changeTesterStatus,
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
            $this->changeTesterStatus->change($profile, $status);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success([], 200, 'Profil mis à jour.');
    }
}
