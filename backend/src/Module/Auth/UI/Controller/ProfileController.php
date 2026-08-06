<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Module\Auth\UI\Response\AuthProfileResponseMapper;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
class ProfileController extends AbstractController
{
    public function __construct(private readonly AuthProfileResponseMapper $profiles)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::success($this->profiles->anonymous());
        }

        return ApiResponse::success($this->profiles->authenticated($user));
    }
}
