<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

final class SupportTimelineEntryFactory
{
    /**
     * @param list<array<string, mixed>> $attachments
     *
     * @return array<string, mixed>
     */
    public function create(
        string $type,
        string $actor,
        string $visibility,
        string $authorLabel,
        ?string $subject,
        ?string $message,
        ?string $status,
        ?\DateTimeImmutable $createdAt = null,
        array $attachments = [],
    ): array {
        return [
            'id' => bin2hex(random_bytes(8)),
            'type' => $type,
            'actor' => $actor,
            'visibility' => $visibility,
            'authorLabel' => trim($authorLabel),
            'subject' => null !== $subject ? trim($subject) : null,
            'message' => null !== $message ? trim($message) : null,
            'status' => $status,
            'attachments' => $attachments,
            'createdAt' => ($createdAt ?? new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }
}
