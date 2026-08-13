<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

final class SupportAttachmentNormalizer
{
    /**
     * @param list<array<string, mixed>> $attachments
     *
     * @return list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>
     */
    public function normalize(array $attachments): array
    {
        return array_values(array_filter(array_map(static function (array $attachment): ?array {
            $name = isset($attachment['name']) && is_string($attachment['name']) ? trim($attachment['name']) : '';
            $originalName = isset($attachment['originalName']) && is_string($attachment['originalName']) ? trim($attachment['originalName']) : '';
            if ('' === $name || '' === $originalName) {
                return null;
            }

            return [
                'name' => $name,
                'originalName' => $originalName,
                'contentType' => isset($attachment['contentType']) && is_string($attachment['contentType']) ? trim($attachment['contentType']) : 'application/octet-stream',
                'size' => isset($attachment['size']) && is_numeric($attachment['size']) ? (int) $attachment['size'] : 0,
                'uploadedAt' => isset($attachment['uploadedAt']) && is_string($attachment['uploadedAt']) ? $attachment['uploadedAt'] : (new \DateTimeImmutable())->format(DATE_ATOM),
            ];
        }, $attachments), static fn (?array $attachment): bool => null !== $attachment));
    }
}
