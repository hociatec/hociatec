<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Controller;

use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Shared\Http\ApiResponse;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers/{id}', methods: ['DELETE'])] #[IsGranted('ROLE_ADMIN')]
final class DeleteBetaTesterController extends AbstractController
{
    public function __construct(private readonly BetaTesterProfileRepository $profiles, private readonly DoctrinePersistence $persistence)
    {
    }

    public function __invoke(int $id): JsonResponse
    {
        $profile = $this->profiles->find($id);
        if (null === $profile) {
            return ApiResponse::error('Profil introuvable.', 404);
        }$this->persistence->remove($profile);
        $this->persistence->flush();

        return ApiResponse::success([], 200, 'Profil supprimé.');
    }
}
