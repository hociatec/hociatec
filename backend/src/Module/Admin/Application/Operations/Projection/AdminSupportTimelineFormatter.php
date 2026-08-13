<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class AdminSupportTimelineFormatter
{
    public function __construct(private AdminSupportTimelineEntryFormatter $entries)
    {
    }

    /**
     * @return list<array{
     *   id:string,
     *   type:string,
     *   actor:string,
     *   visibility:string,
     *   authorLabel:string,
     *   subject:string|null,
     *   message:string|null,
     *   status:string|null,
     *   statusLabel:string|null,
     *   attachments:list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>,
     *   createdAt:string
     * }>
     */
    public function timeline(SupportRequest $support, bool $includeInternal): array
    {
        $timeline = array_values(array_filter(
            $support->getTimeline(),
            static fn (mixed $entry): bool => is_array($entry),
        ));
        $timeline = $this->appendLegacyTimelineEntries($support, $timeline, $includeInternal);

        $timeline = array_values(array_filter(
            $timeline,
            static function (array $entry) use ($includeInternal): bool {
                return $includeInternal || 'internal' !== ($entry['visibility'] ?? 'customer');
            },
        ));

        return array_map(fn (array $entry): array => $this->entries->format($entry), $timeline);
    }

    /**
     * @param list<array<string, mixed>> $timeline
     *
     * @return list<array<string, mixed>>
     */
    private function appendLegacyTimelineEntries(SupportRequest $support, array $timeline, bool $includeInternal): array
    {
        [$hasCustomerMessage, $hasAdminReply] = $this->detectLegacyCoverage($timeline);

        if (!$hasCustomerMessage && null !== $support->getMessage() && '' !== $support->getMessage()) {
            array_unshift($timeline, [
                'id' => 'legacy-initial-message',
                'type' => 'customer_message',
                'actor' => 'customer',
                'visibility' => 'customer',
                'authorLabel' => $support->getCustomer()->getFullName(),
                'subject' => $support->getSubject(),
                'message' => $support->getMessage(),
                'status' => null,
                'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
            ]);
        }

        if (!$hasAdminReply) {
            foreach ($this->legacyAdminRepliesFromNotes($support) as $legacyReply) {
                $timeline[] = $legacyReply;
            }
        }

        if ($includeInternal && [] === array_filter($timeline, static fn (array $entry): bool => ($entry['visibility'] ?? null) === 'internal')) {
            $legacyInternalNote = $this->legacyInternalNote($support);
            if (null !== $legacyInternalNote) {
                $timeline[] = $legacyInternalNote;
            }
        }

        usort($timeline, static function (array $left, array $right): int {
            $leftCreatedAt = is_string($left['createdAt'] ?? null) ? $left['createdAt'] : '';
            $rightCreatedAt = is_string($right['createdAt'] ?? null) ? $right['createdAt'] : '';

            return $leftCreatedAt <=> $rightCreatedAt;
        });

        return $timeline;
    }

    /**
     * @param list<array<string, mixed>> $timeline
     *
     * @return array{0: bool, 1: bool}
     */
    private function detectLegacyCoverage(array $timeline): array
    {
        $hasCustomerMessage = false;
        $hasAdminReply = false;

        foreach ($timeline as $entry) {
            if (($entry['type'] ?? null) === 'customer_message') {
                $hasCustomerMessage = true;
            }

            if (($entry['type'] ?? null) === 'admin_reply') {
                $hasAdminReply = true;
            }
        }

        return [$hasCustomerMessage, $hasAdminReply];
    }

    /** @return list<array<string, mixed>> */
    private function legacyAdminRepliesFromNotes(SupportRequest $support): array
    {
        $notes = $support->getInternalNotes();
        if (null === $notes || '' === trim($notes)) {
            return [];
        }

        $entries = [];
        preg_match_all('/\[(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})\]\s+Réponse envoyée au client\s*:\s*(.+)/u', $notes, $matches, PREG_SET_ORDER);
        foreach ($matches as $index => $match) {
            $createdAt = \DateTimeImmutable::createFromFormat('d/m/Y H:i', trim((string) $match[1]));
            $subject = trim((string) $match[2]);
            $entries[] = [
                'id' => 'legacy-admin-reply-'.$index,
                'type' => 'admin_reply',
                'actor' => 'admin',
                'visibility' => 'customer',
                'authorLabel' => 'Équipe Hociatec',
                'subject' => '' !== $subject ? $subject : 'Réponse SAV',
                'message' => null,
                'status' => null,
                'createdAt' => ($createdAt ?: $support->getUpdatedAt())->format(DATE_ATOM),
            ];
        }

        return $entries;
    }

    /** @return array<string, mixed>|null */
    private function legacyInternalNote(SupportRequest $support): ?array
    {
        $notes = $support->getInternalNotes();
        if (null === $notes || '' === trim($notes)) {
            return null;
        }

        $sanitized = trim((string) preg_replace('/\[\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}\]\s+Réponse envoyée au client\s*:\s*.+/u', '', $notes));
        if ('' === $sanitized) {
            return null;
        }

        return [
            'id' => 'legacy-internal-note',
            'type' => 'internal_note',
            'actor' => 'admin',
            'visibility' => 'internal',
            'authorLabel' => 'Équipe Hociatec',
            'subject' => 'Note interne',
            'message' => $sanitized,
            'status' => null,
            'createdAt' => $support->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
