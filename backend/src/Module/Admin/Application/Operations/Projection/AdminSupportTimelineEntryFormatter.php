<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

final readonly class AdminSupportTimelineEntryFormatter
{
    /**
     * @param array<string, mixed> $entry
     *
     * @return array{
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
     * }
     */
    public function format(array $entry): array
    {
        $status = isset($entry['status']) && is_string($entry['status']) ? $entry['status'] : null;
        $attachments = $entry['attachments'] ?? [];

        return [
            'id' => (string) ($entry['id'] ?? ''),
            'type' => (string) ($entry['type'] ?? 'message'),
            'actor' => (string) ($entry['actor'] ?? 'system'),
            'visibility' => (string) ($entry['visibility'] ?? 'customer'),
            'authorLabel' => (string) ($entry['authorLabel'] ?? 'Hociatec'),
            'subject' => isset($entry['subject']) && is_string($entry['subject']) ? $entry['subject'] : null,
            'message' => isset($entry['message']) && is_string($entry['message']) ? $entry['message'] : null,
            'status' => $status,
            'statusLabel' => null !== $status ? AdminSupportRequestFormatter::statusLabel($status) : null,
            'attachments' => array_values(array_map(
                static fn (array $attachment): array => [
                    'name' => (string) $attachment['name'],
                    'originalName' => isset($attachment['originalName']) && is_string($attachment['originalName']) ? $attachment['originalName'] : (string) $attachment['name'],
                    'contentType' => isset($attachment['contentType']) && is_string($attachment['contentType']) ? $attachment['contentType'] : 'application/octet-stream',
                    'size' => isset($attachment['size']) && is_numeric($attachment['size']) ? (int) $attachment['size'] : 0,
                    'uploadedAt' => isset($attachment['uploadedAt']) && is_string($attachment['uploadedAt']) ? $attachment['uploadedAt'] : '',
                ],
                array_filter(
                    is_array($attachments) ? $attachments : [],
                    static fn (mixed $attachment): bool => is_array($attachment) && is_string($attachment['name'] ?? null),
                ),
            )),
            'createdAt' => isset($entry['createdAt']) && is_string($entry['createdAt']) ? $entry['createdAt'] : '',
        ];
    }
}
