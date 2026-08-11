<?php

declare(strict_types=1);

namespace App\Module\Contact\UI\Controller;

use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Contact\Application\Workflow\ContactFormSubmissionService;
use App\Shared\Application\Exception\MailDeliveryException;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/public/contact', name: 'api_public_contact', methods: ['POST'])]
#[RateLimited('contact_public')]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactFormSubmissionService $submissions,
        private readonly DtoValidator $dtoValidator,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.contact_public')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $input = ContactInput::fromArray($payload);
        $this->dtoValidator->validate($input);
        $limit = $this->limiter->create($this->rateLimitKeys->forRequest($request, $input->email))->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de messages envoyés. Veuillez réessayer plus tard.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS,
            );
        }

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
