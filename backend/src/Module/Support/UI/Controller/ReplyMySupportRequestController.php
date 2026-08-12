<?php

declare(strict_types=1);

namespace App\Module\Support\UI\Controller;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Support\Application\Workflow\CustomerSupportPortalService;
use App\Module\Support\UI\DTO\SupportReplyRequest;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support/me/{id}/reply', name: 'api_support_me_reply', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class ReplyMySupportRequestController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerSupportPortalService $portal,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        try {
            $payload = $request->isMethod('POST') && str_contains((string) $request->headers->get('Content-Type'), 'multipart/form-data')
                ? $request->request->all()
                : \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
            $input = SupportReplyRequest::fromArray($payload);
            $this->validator->validate($input);
            $files = array_values(array_filter($request->files->all('attachments'), static fn ($file) => $file instanceof UploadedFile));
            $item = $this->portal->replyForUser($this->currentUser(), $id, $input->message, $input->subject, $files);
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Demande SAV introuvable.', Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Réponse SAV invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::success(['sent' => true, 'item' => $item]);
    }
}
