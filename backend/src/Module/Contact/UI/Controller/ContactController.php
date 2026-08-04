<?php

declare(strict_types=1);

namespace App\Module\Contact\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\RateLimited;
use App\Infrastructure\Mail\MailDeliveryException;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Contact\Application\Service\ContactSubmissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/contact', name: 'api_public_contact', methods: ['POST'])]
#[RateLimited('contact_public')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactSubmissionService $submissions,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = ContactInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        try {
            $this->submissions->submit($input);
        } catch (MailDeliveryException) {
            return ApiResponse::error(
                "Impossible d'envoyer le message pour le moment.",
                JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return ApiResponse::success([], JsonResponse::HTTP_OK, 'Votre message a été envoyé.');
    }
}
