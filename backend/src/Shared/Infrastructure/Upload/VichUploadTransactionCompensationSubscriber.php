<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Upload;

use App\Shared\Application\TransactionSideEffectRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final readonly class VichUploadTransactionCompensationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TransactionSideEffectRegistry $sideEffects,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_REMOVE => 'backupBeforeRemove',
            Events::POST_UPLOAD => 'removeUploadedFileOnRollback',
        ];
    }

    public function backupBeforeRemove(Event $event): void
    {
        if (!$this->sideEffects->isTracking()) {
            return;
        }

        $path = $this->pathFor($event->getObject(), $event->getMapping());
        if (null === $path || !is_file($path)) {
            return;
        }

        $backup = tempnam(sys_get_temp_dir(), 'hociatec-vich-rollback-');
        if (!is_string($backup) || !copy($path, $backup)) {
            $this->logger->warning('Unable to backup upload before transactional remove.', [
                'mapping' => $event->getMapping()->getMappingName(),
                'path' => $path,
            ]);

            return;
        }

        $this->sideEffects->afterRollback(function () use ($event, $path, $backup): void {
            try {
                $directory = dirname($path);
                if (!is_dir($directory) && !mkdir($directory, recursive: true) && !is_dir($directory)) {
                    throw new \RuntimeException(sprintf('Unable to recreate upload directory "%s".', $directory));
                }

                $this->restoreBackupIfMissing($backup, $path);

                $this->logger->warning('Restored upload removed before database rollback.', [
                    'mapping' => $event->getMapping()->getMappingName(),
                    'path' => $path,
                ]);
            } finally {
                if (is_file($backup)) {
                    unlink($backup);
                }
            }
        });
        $this->sideEffects->afterCommit(static function () use ($backup): void {
            if (is_file($backup)) {
                unlink($backup);
            }
        });
    }

    public function removeUploadedFileOnRollback(Event $event): void
    {
        if (!$this->sideEffects->isTracking()) {
            return;
        }

        $path = $this->pathFor($event->getObject(), $event->getMapping());
        if (null === $path) {
            return;
        }

        $this->sideEffects->afterRollback(function () use ($event, $path): void {
            if (is_file($path) && !unlink($path)) {
                throw new \RuntimeException(sprintf('Unable to remove rolled-back upload "%s".', $path));
            }

            $this->logger->warning('Removed upload created before database rollback.', [
                'mapping' => $event->getMapping()->getMappingName(),
                'path' => $path,
            ]);
        });
    }

    private function pathFor(object $object, PropertyMapping $mapping): ?string
    {
        $filename = $mapping->getFileName($object);
        if (null === $filename || '' === $filename) {
            return null;
        }

        $dir = $mapping->getUploadDir($object);
        $relativePath = is_string($dir) && '' !== $dir
            ? $dir.\DIRECTORY_SEPARATOR.$filename
            : $filename;

        return $mapping->getUploadDestination().\DIRECTORY_SEPARATOR.$relativePath;
    }

    private function restoreBackupIfMissing(string $backup, string $path): void
    {
        clearstatcache(true, $path);
        if (is_file($path)) {
            return;
        }

        if (!copy($backup, $path)) {
            throw new \RuntimeException(sprintf('Unable to restore rolled-back upload "%s".', $path));
        }
    }
}
