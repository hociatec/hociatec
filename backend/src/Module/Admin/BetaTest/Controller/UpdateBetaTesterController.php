<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers/{id}', methods: ['PATCH'])] #[IsGranted('ROLE_ADMIN')]
final class UpdateBetaTesterController extends AbstractController
{
    public function __construct(private readonly BetaTesterProfileRepository $profiles, private readonly DoctrinePersistence $persistence)
    {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $profile = $this->profiles->find($id);
        if (!$profile instanceof BetaTesterProfile) {
            return ApiResponse::error('Profil introuvable.', 404);
        } $status = (string) (JsonPayload::decode($request)['status'] ?? '');
        if (!in_array($status, ['pending', 'accepted', 'paused', 'rejected'], true)) {
            return ApiResponse::error('Statut invalide.', 422);
        } $profile->setStatus($status);
        $this->persistence->flush();

        return ApiResponse::success([], 200, 'Profil mis à jour.');
    }
}
