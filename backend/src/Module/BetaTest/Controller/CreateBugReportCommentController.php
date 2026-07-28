<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Controller;

use App\Module\BetaTest\Entity\BugReportComment;
use App\Module\BetaTest\Repository\BugReportRepository;
use App\Module\BetaTest\Service\BugReportActivityLogger;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\JsonPayload;
use App\Shared\Http\RateLimited;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/beta/reports/{id}/comments', name: 'api_beta_reports_comments_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[RateLimited('beta_report_comment')]
final class CreateBugReportCommentController extends AbstractController
{
    public function __construct(
        private readonly BugReportRepository $reports,
        private readonly DoctrinePersistence $persistence,
        private readonly UserCommunicationNotifier $notifier,
        private readonly UserRepository $users,
        private readonly BugReportActivityLogger $activityLogger,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $report = $this->reports->find($id);
        if (null === $report) {
            return ApiResponse::error('Rapport introuvable.', 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Authentification requise.', 401);
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        if (!$isAdmin && $report->getReporter()->getId() !== $user->getId()) {
            return ApiResponse::error('Accès refusé.', 403);
        }

        $payload = JsonPayload::decode($request);
        $content = trim((string) ($payload['content'] ?? ''));

        if ('' === $content) {
            return ApiResponse::error('Le contenu du message ne peut pas être vide.', 422);
        }

        $comment = new BugReportComment($report, $user, $content);
        $report->recordReply($user);
        $this->persistence->persist($comment);
        $this->activityLogger->log($report, $user, 'comment_added', null, null, mb_substr($content, 0, 500));
        $this->persistence->flush();

        if ($isAdmin && $report->getReporter()->getId() !== $user->getId()) {
            $this->notifier->notify(
                $report->getReporter(),
                sprintf('beta-report-comment:%d:%d', $report->getId(), $comment->getId()),
                'Nouveau message sur un signalement bêta',
                sprintf('Un nouveau message a été ajouté. Titre du signalement : %s.', $report->getTitle()),
                sprintf('/beta?reportId=%d', $report->getId()),
                'beta_report_comment',
            );
        }

        if (!$isAdmin) {
            foreach ($this->users->findAdmins() as $admin) {
                if ($admin->getId() === $user->getId()) {
                    continue;
                }

                $this->notifier->notify(
                    $admin,
                    sprintf('admin-beta-report-comment:%d:%d:%d', $report->getId(), $comment->getId(), $admin->getId()),
                    'Nouveau message client sur un signalement bêta',
                    sprintf('%s a répondu. Titre du signalement : %s.', $user->getFullName(), $report->getTitle()),
                    sprintf('/admin/beta-reports?reportId=%d', $report->getId()),
                    'admin_beta_report_comment',
                );
            }
        }

        return ApiResponse::created([
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'author' => [
                'id' => $comment->getAuthor()->getId(),
                'firstName' => $comment->getAuthor()->getFirstName(),
                'lastName' => $comment->getAuthor()->getLastName(),
                'email' => $comment->getAuthor()->getEmail(),
                'role' => in_array('ROLE_ADMIN', $comment->getAuthor()->getRoles(), true) ? 'admin' : 'user',
            ],
        ], 'Message envoyé.');
    }
}
