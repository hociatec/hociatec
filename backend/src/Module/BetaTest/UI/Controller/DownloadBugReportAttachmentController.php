<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Storage\BetaAttachmentStorage;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\BetaTest\Infrastructure\Repository\BugReportRepository;
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
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly BetaAttachmentStorage $attachments,
        private readonly BugReportAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(int $id, string $name): BinaryFileResponse|JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', Response::HTTP_UNAUTHORIZED);
        }

        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Signalement introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (!$user->isAdmin() && !$this->accessPolicy->canDownloadAttachment($user, $report)) {
            return ApiResponse::error('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        if (!in_array($name, $report->getAttachments(), true)) {
            return ApiResponse::error('Pièce jointe introuvable.', Response::HTTP_NOT_FOUND);
        }

        $path = $this->attachments->path($name);
        if (null === $path) {
            return ApiResponse::error('Fichier introuvable.', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($path);
    }
}
