<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\User\Controller;

use App\Module\Admin\Application\User\DTO\CustomerEmailInput;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Application\Workflow\AdminCustomerEmailService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/send-email', name: 'api_admin_customers_send_email', methods: ['POST'])]
#[IsGranted('ROLE_CUSTOMERS_MANAGER')]
final class SendCustomerEmailController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryPort $users,
        private readonly AdminCustomerEmailService $emails,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = CustomerEmailInput::fromArray($payload);
            $this->validator->validate($input);
            $this->emails->send($user, $input->subject, $input->message);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Envoi d’email invalide.', Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Envoi d’email indisponible.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return ApiResponse::success(['sent' => true], Response::HTTP_OK, 'L’e-mail a bien été envoyé au client.');
    }
}
