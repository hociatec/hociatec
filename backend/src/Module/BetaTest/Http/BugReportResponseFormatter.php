<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Http;

use App\Module\BetaTest\Entity\BugReport;

final readonly class BugReportResponseFormatter
{
    /**
     * @return array<string, mixed>
     */
    public function format(BugReport $report): array
    {
        $id = $report->getId();
        $attachments = array_values(array_filter(
            $report->getAttachments(),
            static fn (string $name): bool => '' !== trim($name),
        ));

        return [
            'id' => $id,
            'title' => $report->getTitle(),
            'description' => $report->getDescription(),
            'expectedBehavior' => $report->getExpectedBehavior(),
            'actualBehavior' => $report->getActualBehavior(),
            'severity' => $report->getSeverity(),
            'status' => $report->getStatus(),
            'pageUrl' => $report->getPageUrl(),
            'reporter' => $report->getReporter()->getEmail(),
            'reporterId' => $report->getReporter()->getId(),
            'reporterName' => $report->getReporter()->getFullName(),
            'assignedTo' => null !== $report->getAssignedTo() ? [
                'id' => $report->getAssignedTo()->getId(),
                'name' => $report->getAssignedTo()->getFullName(),
                'email' => $report->getAssignedTo()->getEmail(),
            ] : null,
            'duplicateOf' => null !== $report->getDuplicateOf() ? [
                'id' => $report->getDuplicateOf()->getId(),
                'title' => $report->getDuplicateOf()->getTitle(),
            ] : null,
            'duplicateReason' => $report->getDuplicateReason(),
            'campaignId' => $report->getCampaign()?->getId(),
            'campaign' => $report->getCampaign()?->getName(),
            'attachments' => $attachments,
            'attachmentUrls' => array_map(
                static fn (string $name): string => sprintf('/api/beta/reports/%d/attachments/%s', $id, rawurlencode($name)),
                $attachments,
            ),
            'createdAt' => $report->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $report->getUpdatedAt()->format(DATE_ATOM),
            'lastAdminReplyAt' => $report->getLastAdminReplyAt()?->format(DATE_ATOM),
            'lastReporterReplyAt' => $report->getLastReporterReplyAt()?->format(DATE_ATOM),
        ];
    }
}
