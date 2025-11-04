<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\DTO\RegisterUserInput;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Service\RegisterUserService;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
class RegisterController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserService $registerUser,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ApiResponse::error('Invalid JSON payload.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $input = RegisterUserInput::fromArray($payload);
        $violations = $this->validator->validate($input);

        if ($violations->count() > 0) {
            return ApiResponse::error('Validation failed.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $this->formatViolations($violations));
        }

        if ($input->password !== $input->confirmPassword) {
            return ApiResponse::error(
                'Validation failed.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ['confirmPassword: Les mots de passe ne correspondent pas.']
            );
        }

        try {
            $user = $this->registerUser->register($input);
        } catch (UserAlreadyExistsException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_CONFLICT);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to register user.', [
                'exception' => $exception,
            ]);

            return ApiResponse::error(
                'Une erreur est survenue pendant la creation du compte.',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                [$exception->getMessage()]
            );
        }

        return ApiResponse::created([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'address' => null,
            'postalCode' => null,
            'city' => null,
            'birthDate' => $user->getBirthDate()->format('Y-m-d'),
            'phoneNumber' => $user->getPhoneNumber(),
            'gender' => $user->getGender(),
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
