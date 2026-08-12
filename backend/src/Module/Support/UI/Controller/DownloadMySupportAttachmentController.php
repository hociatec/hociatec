<?php

declare(strict_types=1);

namespace App\Module\Support\UI\Controller;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Support\Application\Workflow\SupportAttachmentAccessService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/support/me/{id}/attachments/{name}', name: 'api_support_me_attachment', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadMySupportAttachmentController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly SupportAttachmentAccessService $attachments,
        private readonly AttachmentResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(int $id, string $name): BinaryFileResponse|JsonResponse
    {
        try {
            $path = $this->attachments->pathForUser($this->currentUser(), $id, $name);
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Demande SAV introuvable.', Response::HTTP_NOT_FOUND);
        } catch (\DomainException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        if (null === $path) {
            return ApiResponse::error('Pièce jointe introuvable.', Response::HTTP_NOT_FOUND);
        }

        $contentType = mime_content_type($path) ?: 'application/octet-stream';

        return $this->responseFactory->createBinaryFile($path, $name, $contentType);
    }
}
