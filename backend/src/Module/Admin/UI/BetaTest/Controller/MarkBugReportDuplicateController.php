<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\Admin\Application\BetaTest\Handler\MarkBugReportDuplicateHandler;
use App\Module\Admin\Application\BetaTest\Provider\BugReportReferenceProvider;
use App\Module\BetaTest\Application\Port\BugReportRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RequestPayloadMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-reports/{id}/duplicate', name: 'api_admin_beta_reports_duplicate', methods: ['PATCH'])]
#[IsGranted('ROLE_BETA_MANAGER')]
final class MarkBugReportDuplicateController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepositoryPort $reports,
        private readonly BugReportReferenceProvider $references,
        private readonly MarkBugReportDuplicateHandler $markBugReportDuplicate,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        $duplicateOfId = RequestPayloadMapper::int($payload, 'duplicateOfId');
        $reason = RequestPayloadMapper::string($payload, 'reason');
        $actor = \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser::domainUser($this->getUser());

        try {
            $duplicateOf = $this->references->referenceReport($duplicateOfId, $id);
            $this->markBugReportDuplicate->mark($report, $duplicateOf, $reason, $actor instanceof User ? $actor : null);
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Marquage du doublon invalide.', 422);
        } catch (\RuntimeException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Référence de doublon introuvable.', 404);
        }

        return ApiResponse::success(['id' => $report->getId()], 200, 'Signalement marqué comme doublon.');
    }
}
