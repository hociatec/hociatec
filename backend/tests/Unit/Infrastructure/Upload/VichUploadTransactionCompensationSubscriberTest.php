<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Upload;

use App\Shared\Infrastructure\Transaction\InMemoryTransactionSideEffectRegistry;
use App\Shared\Infrastructure\Upload\VichUploadTransactionCompensationSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class VichUploadTransactionCompensationSubscriberTest extends TestCase
{
    public function testUploadedFileIsRemovedWhenDatabaseTransactionRollsBack(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory.'/new-image.jpg';
        file_put_contents($path, 'new');

        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, new NullLogger());
        $object = new class {
            public ?string $imageName = 'new-image.jpg';
        };

        $registry->begin();
        $subscriber->removeUploadedFileOnRollback(new Event($object, $this->mapping($directory)));
        $registry->rollback();

        self::assertFileDoesNotExist($path);
    }

    public function testRemovedFileIsRestoredWhenDatabaseTransactionRollsBack(): void
    {
        $directory = $this->temporaryDirectory();
        $path = $directory.'/old-image.jpg';
        file_put_contents($path, 'old');

        $registry = new InMemoryTransactionSideEffectRegistry(new NullLogger());
        $subscriber = new VichUploadTransactionCompensationSubscriber($registry, new NullLogger());
        $object = new class {
            public ?string $imageName = 'old-image.jpg';
        };

        $registry->begin();
        $subscriber->backupBeforeRemove(new Event($object, $this->mapping($directory)));
        unlink($path);
        $registry->rollback();

        self::assertFileExists($path);
        self::assertSame('old', file_get_contents($path));
    }

    private function mapping(string $directory): PropertyMapping
    {
        $mapping = new PropertyMapping('imageFile', 'imageName');
        $mapping->setMappingName('product_images');
        $mapping->setMapping(['upload_destination' => $directory, 'uri_prefix' => '/uploads/products']);

        return $mapping;
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir().'/hociatec-upload-test-'.bin2hex(random_bytes(8));
        mkdir($directory);

        return $directory;
    }
}
