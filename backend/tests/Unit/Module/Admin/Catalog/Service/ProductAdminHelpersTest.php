<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Catalog\Service;

use App\Module\Admin\Application\Catalog\DTO\ProductWriteData;
use App\Module\Admin\Application\Catalog\Mapper\ProductDiscountRequestMapper;
use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Module\Admin\Application\Catalog\Mapper\ProductGalleryRequestMapper;
use App\Module\Admin\Application\Catalog\Parser\ProductVariantPayloadParser;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class ProductAdminHelpersTest extends TestCase
{
    public function testProductWriteDataBuildsCreateAndUpdateCommands(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $product = new Product('Phone', 'phone', 'SKU-1', 'Desc', 1000, 4, $category);
        $file = new UploadedFile(__FILE__, 'example.php', test: true);
        $startsAt = new \DateTimeImmutable('2026-08-01');
        $endsAt = new \DateTimeImmutable('2026-08-31');

        $data = new ProductWriteData(
            'Phone',
            'SKU-1',
            'phone',
            'Desc',
            'Short',
            1000,
            4,
            true,
            false,
            $category,
            [$file, null, null, null],
            'Alt',
            [0, 2],
            true,
            'sale',
            $brand,
            'group-a',
            2026,
            '256 Go',
            '8 Go',
            'Black',
            [['color' => 'Black', 'storageCapacity' => '256 Go', 'stock' => 4]],
            true,
            'fixed_cents',
            500,
            $startsAt,
            $endsAt,
        );

        $create = $data->toCreateCommand();
        self::assertNull($create->product);
        self::assertSame('Phone', $create->name);
        self::assertSame([], $create->galleryToRemove);
        self::assertFalse($create->removeImage);

        $update = $data->toUpdateCommand($product);
        self::assertSame($product, $update->product);
        self::assertSame([0, 2], $update->galleryToRemove);
        self::assertTrue($update->removeImage);
    }

    public function testFormValueNormalizerCoversBooleanOptionalPriceAndDateCases(): void
    {
        self::assertTrue(ProductFormValueNormalizer::boolean(true));
        self::assertTrue(ProductFormValueNormalizer::boolean('yes'));
        self::assertTrue(ProductFormValueNormalizer::boolean(1));
        self::assertFalse(ProductFormValueNormalizer::boolean('no'));
        self::assertFalse(ProductFormValueNormalizer::boolean([]));

        self::assertSame(12, ProductFormValueNormalizer::optionalInt('12'));
        self::assertNull(ProductFormValueNormalizer::optionalInt(''));
        self::assertSame('Ada', ProductFormValueNormalizer::optionalString(' Ada '));
        self::assertNull(ProductFormValueNormalizer::optionalString([]));

        self::assertSame(1250, ProductFormValueNormalizer::priceToCents('12,50'));
        self::assertSame(1000, ProductFormValueNormalizer::priceToCents(10));
        self::assertSame(1055, ProductFormValueNormalizer::priceToCents(10.55));
        self::assertSame(-1, ProductFormValueNormalizer::priceToCents('bad'));

        self::assertNull(ProductFormValueNormalizer::date(''));
        self::assertSame('2026-08-01', ProductFormValueNormalizer::date('2026-08-01')?->format('Y-m-d'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Date de remise invalide.');
        ProductFormValueNormalizer::date('bad-date');
    }

    public function testGalleryRequestMapperFilesAndRemovals(): void
    {
        $mapper = new ProductGalleryRequestMapper();
        $file = new UploadedFile(__FILE__, 'example.php', test: true);
        $request = new Request([], ['removeGallery' => ['0', ['3'], 'x']], [], [], [
            'gallery' => [0 => $file],
        ]);

        $files = $mapper->files($request);
        self::assertSame($file, $files[0]);
        self::assertNull($files[1]);
        self::assertSame([0, 3], $mapper->removals($request));

        $fallbackRequest = new Request([], ['removeGallery' => '2'], [], [], ['image' => $file]);
        self::assertSame($file, $mapper->files($fallbackRequest)[0]);
        self::assertSame([2], $mapper->removals($fallbackRequest));
    }

    public function testGalleryRequestMapperRejectsInvalidFileAndVariantParserHandlesPayloads(): void
    {
        $mapper = new ProductGalleryRequestMapper();

        try {
            $mapper->files(new Request([], [], [], [], ['gallery' => [0 => new \stdClass()]]));
            self::fail('Expected invalid file exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Fichier d’image invalide.', $exception->getMessage());
        }

        $parser = new ProductVariantPayloadParser();
        self::assertSame([], $parser->parse(null));
        self::assertSame([
            ['color' => 'Blue', 'storageCapacity' => '128 Go', 'stock' => 2],
        ], $parser->parse(json_encode([
            ['color' => ' Blue ', 'storageCapacity' => ' 128 Go ', 'stock' => 2],
            'invalid',
        ], JSON_THROW_ON_ERROR)));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Définition des variantes invalide.');
        $parser->parse('{bad');
    }

    public function testDiscountRequestMapperNormalizesFixedPercentAndInvalidType(): void
    {
        $mapper = new ProductDiscountRequestMapper();

        $fixed = $mapper->map(new Request([], [
            'discountEnabled' => '1',
            'discountType' => 'fixed',
            'discountValue' => '12,50',
            'discountStartsAt' => '2026-08-01',
            'discountEndsAt' => '2026-08-31',
        ]));
        self::assertTrue($fixed['enabled']);
        self::assertSame('fixed_cents', $fixed['type']);
        self::assertSame(1250, $fixed['value']);
        self::assertSame('2026-08-01', $fixed['startsAt']?->format('Y-m-d'));
        self::assertSame('2026-08-31', $fixed['endsAt']?->format('Y-m-d'));

        $percent = $mapper->map(new Request([], [
            'discountType' => 'percent',
            'discountValue' => '12,6',
        ]));
        self::assertSame('percent', $percent['type']);
        self::assertSame(13, $percent['value']);

        $invalid = $mapper->map(new Request([], [
            'discountType' => 'other',
            'discountValue' => '99',
        ]));
        self::assertNull($invalid['type']);
        self::assertNull($invalid['value']);
    }
}
