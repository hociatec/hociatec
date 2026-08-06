<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Catalog\Service;

use App\Module\Admin\Application\Catalog\DTO\ProductWriteData;
use App\Module\Admin\UI\Catalog\Mapper\ProductDiscountRequestMapper;
use App\Module\Admin\Application\Catalog\Normalizer\ProductFormValueNormalizer;
use App\Module\Admin\UI\Catalog\Mapper\ProductGalleryRequestMapper;
use App\Module\Admin\Application\Catalog\Parser\ProductVariantPayloadParser;
use App\Module\Catalog\Application\DTO\ProductCoreWriteData;
use App\Module\Catalog\Application\DTO\ProductDiscountWriteData;
use App\Module\Catalog\Application\DTO\ProductGalleryWriteData;
use App\Module\Catalog\Application\DTO\ProductVariantWriteData;
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
            core: new ProductCoreWriteData(
                name: 'Phone',
                sku: 'SKU-1',
                slug: 'phone',
                description: 'Desc',
                shortDescription: 'Short',
                priceCents: 1000,
                stock: 4,
                isPublished: true,
                isFeaturedHome: false,
                category: $category,
                imageAlt: 'Alt',
                sellingType: 'sale',
                brand: $brand,
            ),
            gallery: new ProductGalleryWriteData(files: [$file, null, null, null], toRemove: [0, 2], removeMainImage: true),
            variant: new ProductVariantWriteData(
                group: 'group-a',
                releaseYear: 2026,
                storageCapacity: '256 Go',
                memoryRam: '8 Go',
                color: 'Black',
                definitions: [['color' => 'Black', 'storageCapacity' => '256 Go', 'stock' => 4]],
            ),
            discount: new ProductDiscountWriteData(
                enabled: true,
                type: 'fixed_cents',
                value: 500,
                startsAt: $startsAt,
                endsAt: $endsAt,
            ),
        );

        $create = $data->toCreateCommand();
        self::assertNull($create->product);
        self::assertSame('Phone', $create->core->name);
        self::assertSame([], $create->gallery->toRemove);
        self::assertFalse($create->gallery->removeMainImage);

        $update = $data->toUpdateCommand($product);
        self::assertSame($product, $update->product);
        self::assertSame([0, 2], $update->gallery->toRemove);
        self::assertTrue($update->gallery->removeMainImage);
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
