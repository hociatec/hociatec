<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Catalog\Service;

use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Catalog\Service\ProductCatalogRules;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ProductCatalogRulesTest extends TestCase
{
    public function testAssertValidDataAcceptsBoundedValuesAndSlugifyNormalizesAccentuatedNames(): void
    {
        $rules = new ProductCatalogRules($this->createMock(ProductRepository::class), Validation::createValidator());

        $rules->assertValidData('Téléphone', 'SKU-1', 'Description', 'Résumé court', 1000, 5);

        self::assertSame('telephone-pro', $rules->slugify(' Téléphone Pro '));
        self::assertSame('produit', $rules->slugify('***'));
    }

    public function testAssertValidDataRejectsInvalidFields(): void
    {
        $rules = new ProductCatalogRules($this->createMock(ProductRepository::class), Validation::createValidator());

        try {
            $rules->assertValidData('', 'BAD SKU!', '', str_repeat('x', 256), -1, 1000001);
            self::fail('Expected validation exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('Le produit doit avoir un nom.', $exception->getMessage());
            self::assertStringContainsString('Le SKU ne peut contenir que des lettres, chiffres, tirets et underscores.', $exception->getMessage());
            self::assertStringContainsString('La description detaillee est obligatoire.', $exception->getMessage());
            self::assertStringContainsString('Le resume est trop long.', $exception->getMessage());
            self::assertStringContainsString('Le prix doit etre positif.', $exception->getMessage());
            self::assertStringContainsString('Le stock est trop eleve.', $exception->getMessage());
        }
    }

    public function testAssertUniquenessAndResolveSlugCoverDuplicateAndGeneratedCases(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $rules = new ProductCatalogRules($repository, Validation::createValidator());

        $repository->expects(self::exactly(5))
            ->method('existsWithSlug')
            ->willReturnOnConsecutiveCalls(true, true, false, false, true);

        $repository->expects(self::exactly(2))
            ->method('existsWithSku')
            ->willReturnOnConsecutiveCalls(false, true);

        $rules->assertUniqueness('SKU-1', null);

        self::assertSame('telephone-3', $rules->resolveSlug(null, 'Téléphone', null));
        self::assertSame('telephone-pro', $rules->resolveSlug(' Téléphone Pro ', 'Ignored', null));

        try {
            $rules->assertUniqueness('SKU-1', 9);
            self::fail('Expected duplicate SKU exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Ce SKU est déjà utilise par un autre produit.', $exception->getMessage());
        }

        try {
            $rules->resolveSlug('telephone-pro', 'Ignored', null);
            self::fail('Expected duplicate slug exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Ce slug est déjà utilisé. Veuillez en choisir un autre.', $exception->getMessage());
        }
    }
}
