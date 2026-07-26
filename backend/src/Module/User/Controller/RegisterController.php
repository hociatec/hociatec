<?php

declare(strict_types=1);

namespace App\Module\User\Controller;

use App\Module\User\DTO\RegisterUserInput;
use App\Module\User\Exception\ActivationEmailDeliveryException;
use App\Module\User\Exception\InvalidBirthDateException;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Service\RegisterUserService;
use App\Module\User\Service\UserProfileFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
class RegisterController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserService $registerUser,
        private readonly DtoValidator $dtoValidator,
        private readonly LoggerInterface $logger,
        private readonly UserProfileFormatter $profiles,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Http\JsonPayload::decode($request);
        $input = RegisterUserInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        try {
            $user = $this->registerUser->register($input);
        } catch (UserAlreadyExistsException $exception) {
            return ApiResponse::error($exception->getMessage(), JsonResponse::HTTP_CONFLICT);
        } catch (InvalidBirthDateException $exception) {
            return ApiResponse::error(
                'Validation des donnees echouee.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                ['birthDate: '.$exception->getMessage()]
            );
        } catch (ActivationEmailDeliveryException $exception) {
            $this->logger->warning('Registration rolled back after activation email failure.', [
                'exception' => $exception,
            ]);

            return ApiResponse::error(
                "Le compte n'a pas ete cree car l'e-mail d'activation n'a pas pu etre envoye. Reessayez dans quelques instants.",
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return ApiResponse::created($this->profiles->format($user), 'Compte créé. Vérifiez votre adresse e-mail pour activer votre compte.');
    }
}
