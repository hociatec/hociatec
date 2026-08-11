<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Admin\Application\Quote\Handler\CreateQuoteServiceHandler;
use App\Module\Admin\Application\Quote\Handler\UpdateQuoteServiceHandler;
use App\Module\Admin\UI\Quote\Mapper\QuoteServiceFormMapper;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class QuoteServiceFormHandlersAndMapperTest extends MiscSupportTestCase
{
    public function testQuoteServiceHandlersRejectInconsistentFormData(): void
    {
        $formApplier = new QuoteServiceFormApplier();
        $createService = new CreateQuoteServiceHandler(new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)), $formApplier);
        $updateService = new UpdateQuoteServiceHandler(new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)), $formApplier);

        try {
            $createService->create(new QuoteServiceFormData([
                'title' => 'Audit',
                'description' => null,
                'billingMode' => null,
                'durationValue' => null,
                'durationUnit' => null,
                'priceCents' => 1000,
                'vatRateBps' => null,
                'isFeaturedHome' => false,
                'imageFile' => null,
                'imageUrl' => null,
                'imageAlt' => null,
                'updatesBillingMode' => true,
                'updatesDuration' => false,
            ]));
            self::fail('Expected invalid billing mode exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Mode de facturation invalide.', $exception->getMessage());
        }

        try {
            $updateService->update(
                new ServiceOffering('Audit', 1000, 2000),
                new QuoteServiceFormData([
                    'title' => 'Audit',
                    'description' => null,
                    'billingMode' => 'hour',
                    'durationValue' => 2,
                    'durationUnit' => null,
                    'priceCents' => 1000,
                    'vatRateBps' => null,
                    'isFeaturedHome' => false,
                    'imageFile' => null,
                    'imageUrl' => null,
                    'imageAlt' => null,
                    'updatesBillingMode' => true,
                    'updatesDuration' => true,
                ]),
            );
            self::fail('Expected invalid duration exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La durée doit contenir une valeur et une unité.', $exception->getMessage());
        }

        try {
            $updateService->update(
                new ServiceOffering('Audit', 1000, 2000),
                new QuoteServiceFormData([
                    'title' => 'Audit',
                    'description' => null,
                    'billingMode' => null,
                    'durationValue' => null,
                    'durationUnit' => null,
                    'priceCents' => -1,
                    'vatRateBps' => null,
                    'isFeaturedHome' => false,
                    'imageFile' => null,
                    'imageUrl' => null,
                    'imageAlt' => null,
                    'updatesBillingMode' => false,
                    'updatesDuration' => false,
                ]),
            );
            self::fail('Expected invalid price exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Prix invalide.', $exception->getMessage());
        }
    }

    public function testQuoteServiceFormMapperNormalizesCreateAndUpdatePayloads(): void
    {
        $mapper = new QuoteServiceFormMapper();

        $created = $mapper->create(new Request([], [
            'title' => ' Audit ',
            'description' => '',
            'unit' => 'HEURE',
            'durationValue' => '0',
            'durationUnit' => 'week',
            'price' => 12.5,
            'vatRate' => '',
        ]));
        self::assertSame('Audit', $created->title);
        self::assertNull($created->description);
        self::assertSame('horaire', $created->billingMode);
        self::assertNull($created->durationValue);
        self::assertNull($created->durationUnit);
        self::assertSame(1250, $created->priceCents);
        self::assertSame(0, $created->vatRateBps);
        self::assertFalse($created->isFeaturedHome);
        self::assertNull($created->imageUrl);
        self::assertNull($created->imageAlt);
        self::assertTrue($created->updatesBillingMode);
        self::assertTrue($created->updatesDuration);

        $service = (new ServiceOffering('Base', 1000, 2000))
            ->setDescription('Desc')
            ->setUnit('jour')
            ->setDurationValue(3)
            ->setDurationUnit('day');
        $updated = $mapper->update(new Request([], [
            'title' => ' Premium ',
            'description' => '  ',
            'unit' => 'unknown',
            'durationValue' => '',
            'durationUnit' => 'hour',
            'price' => 'oops',
            'vatRate' => '5,5',
            'isFeaturedHome' => '1',
            'imageUrl' => ' https://example.com/premium.svg ',
            'imageAlt' => ' Premium alt ',
        ]), $service);
        self::assertSame('Premium', $updated->title);
        self::assertNull($updated->description);
        self::assertNull($updated->billingMode);
        self::assertNull($updated->durationValue);
        self::assertSame('hour', $updated->durationUnit);
        self::assertSame(-1, $updated->priceCents);
        self::assertSame(550, $updated->vatRateBps);
        self::assertTrue($updated->isFeaturedHome);
        self::assertSame('https://example.com/premium.svg', $updated->imageUrl);
        self::assertSame('Premium alt', $updated->imageAlt);
        self::assertTrue($updated->updatesBillingMode);
        self::assertTrue($updated->updatesDuration);

        $unchanged = $mapper->update(new Request([], ['title' => 'Same']), $service);
        self::assertSame('jour', $unchanged->billingMode);
        self::assertSame(3, $unchanged->durationValue);
        self::assertSame('day', $unchanged->durationUnit);
        self::assertNull($unchanged->priceCents);
        self::assertNull($unchanged->vatRateBps);
        self::assertFalse($unchanged->updatesDuration);
    }
}
