<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Workflow\CustomerBugReportPortalService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}/attachments/{name}', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class DownloadBugReportAttachmentController extends AbstractController
{
    public function __construct(private readonly CustomerBugReportPortalService $portal)
    {
    }

    public function __invoke(int $id, string $name): BinaryFileResponse|JsonResponse
    {
        $user = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $path = $this->portal->attachmentPathForUser($user, $id, $name);
        } catch (\DomainException $exception) {
            return ApiResponse::error('Accès refusé.', Response::HTTP_FORBIDDEN);
        }
        if (null === $path) {
            return ApiResponse::error('Pièce jointe introuvable.', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($path);
    }
}
