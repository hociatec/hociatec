<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\BetaTest\Infrastructure\Http\BetaProfileResponseFormatter;
use App\Module\BetaTest\Infrastructure\Repository\BetaTesterProfileRepository;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/profile', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class GetMyBetaProfileController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepository $profiles,
        private readonly BetaProfileResponseFormatter $formatter,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $profile = $this->profiles->findOneByUser($user);
        if (null === $profile) {
            return ApiResponse::error('Profil bêta introuvable.', 404);
        }

        return ApiResponse::success(['profile' => $this->formatter->format($profile)]);
    }
}
