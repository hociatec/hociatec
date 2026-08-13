<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Support\Application\Workflow\SupportAttachmentAccessService;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/support-requests')]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class SupportAttachmentController
{
    public function __construct(
        private AttachmentResponseFactory $attachments,
        private SupportAttachmentAccessService $attachmentAccess,
    ) {
    }

    #[Route('/{id}/attachments/{name}', name: 'api_admin_operations_support_attachment', methods: ['GET'])]
    public function __invoke(int $id, string $name): BinaryFileResponse|JsonResponse
    {
        try {
            $path = $this->attachmentAccess->pathForAdmin($id, $name);
        } catch (OperationsResourceNotFoundException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Demande SAV introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (null === $path) {
            return ApiResponse::error('Pièce jointe introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->attachments->createBinaryFile($path, $name, mime_content_type($path) ?: 'application/octet-stream');
    }
}
