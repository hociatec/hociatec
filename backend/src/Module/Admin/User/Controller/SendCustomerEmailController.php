<?php

declare(strict_types=1);

namespace App\Module\Admin\User\Controller;

use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\AdminCustomerEmailService;
use App\Shared\Http\ApiResponse;
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
    ) {
    }

    public function __invoke(int $userId, Request $request): JsonResponse
    {
        $user = $this->users->find($userId);
        if (null === $user) {
            return ApiResponse::error('Client introuvable.', Response::HTTP_NOT_FOUND);
        }

        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->emails->send(
                $user,
                (string) ($payload['subject'] ?? ''),
                (string) ($payload['message'] ?? ''),
            );
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return ApiResponse::success(['sent' => true]);
    }
}
