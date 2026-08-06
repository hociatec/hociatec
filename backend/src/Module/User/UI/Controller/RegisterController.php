<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller;

use App\Module\User\Application\DTO\RegisterUserInput;
use App\Module\User\Application\Exception\ActivationEmailDeliveryException;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Application\Projection\UserProfileFormatter;
use App\Module\User\Application\Workflow\RegisterUserService;
use App\Module\User\UI\Http\RegistrationRateLimiter;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\CsrfExempt;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
#[CsrfExempt]
class RegisterController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserService $registerUser,
        private readonly DtoValidator $dtoValidator,
        private readonly LoggerInterface $logger,
        private readonly UserProfileFormatter $profiles,
        private readonly RegistrationRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $email = is_string($payload['email'] ?? null) ? $payload['email'] : null;
        if (!$this->rateLimiter->isAccepted($request, $email)) {
            return ApiResponse::error(
                'Trop de tentatives d’inscription. Veuillez réessayer plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

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
