<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Workflow\CustomerBugReportPortalService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}/attachments/{name}', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadBugReportAttachmentController extends AbstractController
{
    public function __construct(
        private readonly CustomerBugReportPortalService $portal,
        private readonly AttachmentResponseFactory $attachments,
        private readonly RateLimitKeyFactory $rateLimitKeys,
        #[Autowire(service: 'limiter.private_file_download')]
        private readonly RateLimiterFactory $limiter,
    ) {
    }

    public function __invoke(int $id, string $name, Request $request): BinaryFileResponse|JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', Response::HTTP_UNAUTHORIZED);
        }

        $limit = $this->limiter->create($this->rateLimitKeys->forRequest($request, $user->getEmail().':beta-attachment'))->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error('Trop de téléchargements privés. Veuillez réessayer plus tard.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $path = $this->portal->attachmentPathForUser($user, $id, $name);
        } catch (\DomainException $exception) {
            return ApiResponse::error('Accès refusé.', Response::HTTP_FORBIDDEN);
        }
        if (null === $path) {
            return ApiResponse::error('Pièce jointe introuvable.', Response::HTTP_NOT_FOUND);
        }

        $contentType = mime_content_type($path) ?: 'application/octet-stream';

        return $this->attachments->createBinaryFile($path, $name, $contentType);
    }
}
