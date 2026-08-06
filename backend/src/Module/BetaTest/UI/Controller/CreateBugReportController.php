<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Controller;

use App\Module\BetaTest\Application\Port\BetaCampaignRepositoryPort;
use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Application\Writer\BugReportWriter;
use App\Module\BetaTest\Domain\Enum\BetaTesterStatus;
use App\Module\BetaTest\Domain\Exception\BetaTestOperationException;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports', methods: ['POST'])] #[IsGranted('ROLE_USER')]
#[RateLimited('beta_report_create')]
final class CreateBugReportController extends AbstractController
{
    public function __construct(
        private readonly BetaCampaignRepositoryPort $campaigns,
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly BugReportWriter $writer,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $profile = $this->profiles->findOneByUser($user);
        if (null === $profile || BetaTesterStatus::ACCEPTED->value !== $profile->getStatus()) {
            return ApiResponse::error('Votre profil bêta doit être accepté avant d’envoyer un signalement.', 403);
        }

        $payload = $request->isMethod('POST') && str_contains((string) $request->headers->get('Content-Type'), 'multipart/form-data') ? $request->request->all() : \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $campaign = null;
        if (isset($payload['campaignId'])) {
            $campaign = $this->campaigns->find((int) $payload['campaignId']);
            if (null === $campaign) {
                return ApiResponse::error('La campagne demandée est introuvable.', 404);
            }

            if (!$campaign->isOpenForReports()) {
                return ApiResponse::error('Cette campagne n’est plus ouverte aux signalements.', 422);
            }
        }

        $files = array_values(array_filter($request->files->all('screenshots'), static fn ($file) => $file instanceof UploadedFile));
        try {
            $report = $this->writer->create($user, $campaign, $payload, $files);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        } catch (BetaTestOperationException $exception) {
            return ApiResponse::internalError($exception->getMessage());
        }

        return ApiResponse::createdItem('id', $report->getId(), 'Votre signalement a bien été envoyé.');
    }
}
