<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\Handler\CreateQuoteServiceHandler;
use App\Module\Admin\Application\Quote\Handler\UpdateQuoteServiceHandler;
use App\Module\Admin\UI\Quote\Controller\CreateServiceController;
use App\Module\Admin\UI\Quote\Controller\GetServiceController;
use App\Module\Admin\UI\Quote\Controller\ListServicesController;
use App\Module\Admin\UI\Quote\Controller\UpdateServiceController;
use App\Module\Admin\UI\Quote\Mapper\QuoteServiceFormMapper;
use App\Module\Service\Domain\Entity\ServiceOffering;
use App\Module\Service\Infrastructure\Repository\ServiceOfferingRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminQuoteServiceCatalogControllersTest extends MiscSupportTestCase
{
    public function testServiceCatalogControllers(): void
    {
        $repository = $this->createMock(ServiceOfferingRepository::class);
        $service = new ServiceOffering('Audit', 12000, 2000);
        $this->setId($service, 12);
        $service
            ->setDescription('Desc')
            ->setUnit('horaire')
            ->setDurationValue(2)
            ->setDurationUnit('hour')
            ->setIsFeaturedHome(true)
            ->setImageExternalUrl('https://example.com/audit.svg')
            ->setImageAlt('Illustration audit');

        $repository->expects(self::exactly(6))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $service, null, $service, $service, $service);
        $repository->expects(self::once())
            ->method('findForAdmin')
            ->with(null, 10, 10)
            ->willReturn([$service]);
        $repository->expects(self::once())->method('countForAdmin')->with(null)->willReturn(21);

        $get = new GetServiceController($repository, $this->serviceFormatter());
        self::assertSame(Response::HTTP_NOT_FOUND, $get(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $get(12)->getStatusCode());

        $list = new ListServicesController($repository, $this->serviceFormatter());
        $listPayload = json_decode((string) $list(new Request(['page' => '2']))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $listPayload['data']['meta']['page']);
        self::assertSame('Audit', $listPayload['data']['items'][0]['title']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(ServiceOffering::class));
        $entityManager->expects(self::exactly(2))->method('flush');
        $formApplier = new QuoteServiceFormApplier();
        $forms = new QuoteServiceFormMapper();

        $create = new CreateServiceController(
            $forms,
            new CreateQuoteServiceHandler(new DoctrineUnitOfWork($entityManager), $formApplier),
            $this->serviceFormatter(),
        );
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $create(new Request([], ['title' => '', 'price' => '-5']))->getStatusCode());
        $createdPayload = json_decode((string) $create(new Request([], [
            'title' => 'Installation',
            'description' => 'Desc',
            'unit' => 'jour',
            'durationValue' => '2',
            'durationUnit' => 'day',
            'price' => '250',
            'vatRate' => '20',
            'isFeaturedHome' => '1',
            'imageUrl' => 'https://example.com/installation.svg',
            'imageAlt' => 'Illustration installation',
        ]))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Installation', $createdPayload['data']['title']);
        self::assertSame('2 jours', $createdPayload['data']['durationLabel']);
        self::assertTrue($createdPayload['data']['isFeaturedHome']);
        self::assertSame('https://example.com/installation.svg', $createdPayload['data']['imageUrl']);
        self::assertSame('Illustration installation', $createdPayload['data']['imageAlt']);

        $update = new UpdateServiceController(
            $repository,
            $forms,
            new UpdateQuoteServiceHandler(new DoctrineUnitOfWork($entityManager), $formApplier),
            $this->serviceFormatter(),
        );
        self::assertSame(Response::HTTP_NOT_FOUND, $update(new Request([], ['title' => 'x']), 404)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $update(new Request([], ['title' => 'Audit', 'unit' => 'oops']), 12)->getStatusCode());
        $updatedPayload = json_decode((string) $update(new Request([], [
            'title' => 'Audit premium',
            'unit' => 'jour',
            'durationValue' => '3',
            'durationUnit' => 'day',
            'price' => '300',
            'vatRate' => '5.5',
            'isFeaturedHome' => '1',
        ]), 12)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Audit premium', $updatedPayload['data']['title']);
        self::assertSame(550.0, $updatedPayload['data']['vatRate'] * 100);
        self::assertTrue($updatedPayload['data']['isFeaturedHome']);
        self::assertSame('https://example.com/audit.svg', $updatedPayload['data']['imageUrl']);

        $failingEntityManager = $this->createMock(EntityManagerInterface::class);
        $failingEntityManager->expects(self::once())->method('persist')->willThrowException(new \RuntimeException('db down'));
        $failingCreate = new CreateServiceController(
            $forms,
            new CreateQuoteServiceHandler(new DoctrineUnitOfWork($failingEntityManager), $formApplier),
            $this->serviceFormatter(),
        );
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $failingCreate(new Request([], ['title' => 'Installation', 'price' => '250']))->getStatusCode());

        $failingEntityManager2 = $this->createMock(EntityManagerInterface::class);
        $failingEntityManager2->expects(self::once())->method('flush')->willThrowException(new \RuntimeException('db down'));
        $failingUpdate = new UpdateServiceController(
            $repository,
            $forms,
            new UpdateQuoteServiceHandler(new DoctrineUnitOfWork($failingEntityManager2), $formApplier),
            $this->serviceFormatter(),
        );
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $failingUpdate(new Request([], ['title' => 'Audit premium', 'price' => '300']), 12)->getStatusCode());
    }
}
