<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class BetaAttachmentStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<mixed> $files
     *
     * @return list<string>
     */
    public function store(array $files): array
    {
        $directory = $this->projectDir.'/var/beta-attachments';
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        } $paths = [];
        foreach (array_slice($files, 0, 5) as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid() || !in_array(strtolower((string) $file->getMimeType()), ['image/png', 'image/jpeg', 'image/webp'], true) || $file->getSize() > 5 * 1024 * 1024) {
                continue;
            } $name = bin2hex(random_bytes(16)).'.'.($file->guessExtension() ?: 'bin');
            $file->move($directory, $name);
            $paths[] = $name;
        }

        return $paths;
    }

    public function path(string $name): ?string
    {
        $safeName = basename($name);
        if ($safeName !== $name || '' === $safeName) {
            return null;
        }

        $path = $this->projectDir.'/var/beta-attachments/'.$safeName;

        return is_file($path) ? $path : null;
    }

    /**
     * @param list<mixed> $names
     */
    public function deleteMany(array $names): void
    {
        foreach ($names as $name) {
            if (!is_string($name)) {
                continue;
            }

            $path = $this->path($name);
            if (null !== $path && !unlink($path)) {
                $this->logger->warning('Beta attachment cleanup failed.', ['path' => $path]);
            }
        }
    }
}
