<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\User\Entity\User;
use App\Shared\Http\ApiResponse;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/profile', methods: ['DELETE'])] #[IsGranted('ROLE_USER')]
final class LeaveBetaProgramController extends AbstractController
{
    public function __construct(private readonly BetaTesterProfileRepository $profiles, private readonly DoctrinePersistence $persistence)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        } $profile = $this->profiles->findOneByUser($user);
        if (null !== $profile) {
            $this->persistence->remove($profile);
            $this->persistence->flush();
        }

        return ApiResponse::success([], 200, 'Vos données bêta ont été supprimées.');
    }
}
