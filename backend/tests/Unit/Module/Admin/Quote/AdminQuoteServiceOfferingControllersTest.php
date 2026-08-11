<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Quote;

use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\Handler\CreateQuoteServiceHandler;
use App\Module\Admin\Application\Quote\Handler\UpdateQuoteServiceHandler;
use App\Module\Admin\UI\Quote\Controller\CreateServiceController;
use App\Module\Admin\UI\Quote\Controller\DeleteServiceController;
use App\Module\Admin\UI\Quote\Controller\GetServiceController;
use App\Module\Admin\UI\Quote\Controller\ListServicesController;
use App\Module\Admin\UI\Quote\Controller\UpdateServiceController;
use App\Module\Admin\UI\Quote\Mapper\QuoteServiceFormMapper;
use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminQuoteServiceOfferingControllersTest extends AdminQuoteIntegrationTestCase
{
    public function testServiceOfferingControllers(): void
    {
        $em = $this->entityManager();
        $serviceRepository = $this->serviceRepository($em);
        $quoteFormatter = $this->quoteFormatter();

        $formApplier = new QuoteServiceFormApplier();
        $createQuoteService = new CreateQuoteServiceHandler(new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em), $formApplier);
        $updateQuoteService = new UpdateQuoteServiceHandler(new \App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork($em), $formApplier);
        $formMapper = new QuoteServiceFormMapper();
        $createService = new CreateServiceController($formMapper, $createQuoteService, $quoteFormatter);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $createService(Request::create('/', 'POST', ['title' => '', 'price' => 10]))->getStatusCode());
        $createdService = $createService(Request::create('/', 'POST', ['title' => 'Audit', 'description' => 'Desc', 'unit' => 'jour', 'durationValue' => '2', 'durationUnit' => 'day', 'price' => '120,50', 'vatRate' => '20']));
        self::assertSame(Response::HTTP_CREATED, $createdService->getStatusCode());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new CreateServiceController($formMapper, $this->throwingCreateQuoteService(), $quoteFormatter))(Request::create('/', 'POST', ['title' => 'Down', 'price' => 10]))->getStatusCode());
        $serviceId = (int) $this->payload($createdService)['data']['id'];

        self::assertSame(Response::HTTP_OK, (new ListServicesController($serviceRepository, $quoteFormatter))(Request::create('/?page=1&perPage=5'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new GetServiceController($serviceRepository, $quoteFormatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new GetServiceController($serviceRepository, $quoteFormatter))($serviceId)->getStatusCode());

        $updateService = new UpdateServiceController($serviceRepository, $formMapper, $updateQuoteService, $quoteFormatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $updateService(Request::create('/', 'POST', ['title' => 'x']), 999)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $updateService(Request::create('/', 'POST', ['unit' => 'bogus']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $updateService(Request::create('/', 'POST', ['durationValue' => '2', 'durationUnit' => '']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $updateService(Request::create('/', 'POST', ['title' => 'Audit', 'price' => 'abc']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $updateService(Request::create('/', 'POST', ['title' => 'Audit updated', 'price' => '90', 'durationValue' => '1', 'durationUnit' => 'hour']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $updateService(Request::create('/', 'POST', ['title' => 'Audit partial']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new UpdateServiceController($serviceRepository, $formMapper, $this->throwingUpdateQuoteService(), $quoteFormatter))(Request::create('/', 'POST', ['title' => 'Down']), $serviceId)->getStatusCode());

        $deleteService = new DeleteServiceController($serviceRepository, new \App\Module\Admin\Application\Quote\Handler\DeleteQuoteServiceHandler(
            $serviceRepository,
            new QuotePersistence($em),
        ));
        self::assertSame(Response::HTTP_NOT_FOUND, $deleteService(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $deleteService($serviceId)->getStatusCode());
    }
}
