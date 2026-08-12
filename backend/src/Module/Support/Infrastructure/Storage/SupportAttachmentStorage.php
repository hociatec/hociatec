<?php

declare(strict_types=1);

namespace App\Module\Support\Infrastructure\Storage;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class SupportAttachmentStorage
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<mixed> $files
     *
     * @return list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>
     */
    public function store(array $files): array
    {
        $directory = $this->projectDir.'/var/support-attachments';
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $stored = [];
        foreach (array_slice($files, 0, 5) as $file) {
            if (
                !$file instanceof UploadedFile ||
                !$file->isValid() ||
                !in_array(strtolower((string) $file->getMimeType()), self::ALLOWED_MIME_TYPES, true) ||
                $file->getSize() > 5 * 1024 * 1024
            ) {
                continue;
            }

            $name = bin2hex(random_bytes(16)).'.'.($file->guessExtension() ?: 'bin');
            $file->move($directory, $name);
            $stored[] = [
                'name' => $name,
                'originalName' => trim((string) $file->getClientOriginalName()) ?: $name,
                'contentType' => strtolower((string) $file->getMimeType()),
                'size' => (int) ($file->getSize() ?? 0),
                'uploadedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ];
        }

        return $stored;
    }

    public function path(string $name): ?string
    {
        $safeName = basename($name);
        if ($safeName !== $name || '' === $safeName) {
            return null;
        }

        $path = $this->projectDir.'/var/support-attachments/'.$safeName;

        return is_file($path) ? $path : null;
    }

    /**
     * @param list<array<string, mixed>> $attachments
     */
    public function deleteMany(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $name = isset($attachment['name']) && is_string($attachment['name']) ? $attachment['name'] : null;
            if (null === $name) {
                continue;
            }

            $path = $this->path($name);
            if (null !== $path && !unlink($path)) {
                $this->logger->warning('Support attachment cleanup failed.', ['path' => $path]);
            }
        }
    }
}
