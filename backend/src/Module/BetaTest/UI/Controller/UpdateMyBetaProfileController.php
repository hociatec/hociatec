<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\DTO\BetaProfileInput;
use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Application\Workflow\BetaTesterProfileService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/profile', methods: ['PUT'])] #[IsGranted('ROLE_USER')]
final class UpdateMyBetaProfileController extends AbstractController
{
    public function __construct(
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly DtoValidator $validator,
        private readonly BetaTesterProfileService $profileService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }
        $profile = $this->profiles->findOneByUser($user);
        $input = \App\Shared\Infrastructure\Http\JsonRequestInput::decode($request, BetaProfileInput::class);
        $this->validator->validate($input);
        $this->profileService->save($user, $profile, $input);

        return ApiResponse::success([], 200, 'Profil bêta mis à jour.');
    }
}
