<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Application\Service\UpdateProfileService;
use App\Module\User\Application\Service\UserProfileFormatter;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth/profile', name: 'api_auth_profile_update', methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
class UpdateProfileController extends AbstractController
{
    public function __construct(
        private readonly UpdateProfileService $updateProfile,
        private readonly DtoValidator $dtoValidator,
        private readonly UserProfileFormatter $profiles,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = JsonPayload::decode($request);

        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $input = UpdateProfileInput::fromArray($payload);
        $this->dtoValidator->validate($input, ['newPassword' => 'password']);

        try {
            $updatedUser = $this->updateProfile->update($user, $input);
        } catch (UserAlreadyExistsException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_CONFLICT);
        } catch (InvalidBirthDateException $exception) {
            return ApiResponse::error(
                'Validation des donnees echouee.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ['birthDate: '.$exception->getMessage()]
            );
        } catch (InvalidCurrentPasswordException $exception) {
            return ApiResponse::error(
                'Validation des donnees echouee.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ['currentPassword: '.$exception->getMessage()]
            );
        } catch (InvalidProfilePasswordException $exception) {
            return ApiResponse::error(
                'Validation des donnees echouee.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ['password: '.$exception->getMessage()]
            );
        }

        return ApiResponse::success($this->profiles->format($updatedUser));
    }
}
