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
            static fn (mixed $name): bool => is_string($name) && '' !== trim($name),
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
            'campaignId' => $report->getCampaign()?->getId(),
            'campaign' => $report->getCampaign()?->getName(),
            'attachments' => $attachments,
            'attachmentUrls' => array_map(
                static fn (string $name): string => sprintf('/api/beta/reports/%d/attachments/%s', $id, rawurlencode($name)),
                $attachments,
            ),
            'createdAt' => $report->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
