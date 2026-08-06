<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\UI\Http\BetaProfileResponseFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/profile', methods: ['GET'])] #[IsGranted('ROLE_USER')]
final class GetMyBetaProfileController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly BetaProfileResponseFormatter $formatter,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $profile = $this->profiles->findOneByUser($user);
        if (null === $profile) {
            return ApiResponse::successItem('profile', null);
        }

        return ApiResponse::successItem('profile', $this->formatter->format($profile));
    }
}
