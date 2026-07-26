<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\Admin\User\DTO\CustomerEmailInput;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\AdminCustomerEmailService;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/customers/{userId}/send-email', name: 'api_admin_customers_send_email', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class SendCustomerEmailController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
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
            $payload = \App\Shared\Http\JsonPayload::decode($request);
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = CustomerEmailInput::fromArray($payload);
            $this->validator->validate($input);
            $this->emails->send($user, $input->subject, $input->message);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return ApiResponse::success(['sent' => true], Response::HTTP_OK, 'L’e-mail a bien été envoyé au client.');
    }
}
