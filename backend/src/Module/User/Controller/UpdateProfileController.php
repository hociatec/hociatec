<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\DTO\UpdateProfileInput;
use App\Module\User\Entity\User;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Service\UpdateProfileService;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Shared\Http\ApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

#[Route('/api/auth/profile', name: 'api_auth_profile_update', methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
class UpdateProfileController extends AbstractController
{
    public function __construct(
        private readonly UpdateProfileService $updateProfile,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly ShippingAddressRepository $addresses,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        $input = UpdateProfileInput::fromArray($payload);
        $violations = $this->validator->validate($input);

        if ($violations->count() > 0) {
            return ApiResponse::error(
                'Validation des donnees echouee.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $this->formatViolations($violations)
            );
        }

        $newPassword = isset($payload['password']) ? (string) $payload['password'] : null;
        if ($newPassword !== null && $newPassword !== '') {
            if (mb_strlen($newPassword) < 8) {
                return ApiResponse::error(
                    'Validation des donnees echouee.',
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    ['password: Ce champ doit contenir au moins 8 caracteres.']
                );
            }

            if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $newPassword)) {
                return ApiResponse::error(
                    'Validation des donnees echouee.',
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                    ['password: Le mot de passe doit contenir au moins une majuscule et un chiffre.']
                );
            }
        }

        try {
            $updatedUser = $this->updateProfile->update($user, $input, $newPassword);
        } catch (UserAlreadyExistsException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_CONFLICT);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to update profile.', [
                'exception' => $exception,
            ]);

            return ApiResponse::error(
                'Impossible de mettre a jour votre profil pour le moment.',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                [$exception->getMessage()]
            );
        }

        $default = $this->addresses->findDefaultForUser($updatedUser) ?? $this->addresses->findFirstForUser($updatedUser);

        return ApiResponse::success([
            'id' => $updatedUser->getId(),
            'email' => $updatedUser->getEmail(),
            'firstName' => $updatedUser->getFirstName(),
            'lastName' => $updatedUser->getLastName(),
            'roles' => $updatedUser->getRoles(),
            'address' => $default?->getAddress(),
            'postalCode' => $default?->getPostalCode(),
            'city' => $default?->getCity(),
            'birthDate' => $updatedUser->getBirthDate()->format('Y-m-d'),
            'phoneNumber' => $updatedUser->getPhoneNumber(),
            'gender' => $updatedUser->getGender(),
        ]);
    }

    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        return $errors;
    }
}
