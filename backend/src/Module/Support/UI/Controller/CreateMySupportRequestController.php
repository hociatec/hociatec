<?php

declare(strict_types=1);

namespace App\Module\Support\UI\Controller;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\Workflow\CustomerSupportPortalService;
use App\Module\Support\UI\DTO\SupportCreateRequest;
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

#[Route('/api/support/me', name: 'api_support_me_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class CreateMySupportRequestController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly CustomerSupportPortalService $portal,
        private readonly DtoValidator $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->isMethod('POST') && str_contains((string) $request->headers->get('Content-Type'), 'multipart/form-data')
                ? $request->request->all()
                : \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
            $input = SupportCreateRequest::fromArray($payload);
            $this->validator->validate($input);
            $files = array_values(array_filter($request->files->all('attachments'), static fn ($file) => $file instanceof UploadedFile));
            $item = $this->portal->createForUser(
                $this->currentUser(),
                new SupportCreateData($this->currentUser()->getId() ?? 0, $input->subject, $input->reason, $input->message, null, $input->orderId),
                $files,
            );
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Commande introuvable.', Response::HTTP_NOT_FOUND);
        } catch (InvalidJsonPayloadException|\JsonException|\RuntimeException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::createdItem('item', $item, 'Votre demande SAV a bien été enregistrée.');
    }
}
