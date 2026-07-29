<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Quote\Controller\CreateServiceController;
use App\Module\Admin\Quote\Controller\DeleteQuoteController;
use App\Module\Admin\Quote\Controller\DuplicateQuoteController;
use App\Module\Admin\Quote\Controller\GetServiceController;
use App\Module\Admin\Quote\Controller\ListServicesController;
use App\Module\Admin\Quote\Controller\ShowQuoteController;
use App\Module\Admin\Quote\Controller\UpdateQuoteStatusController;
use App\Module\Admin\Quote\Controller\UpdateServiceController;
use App\Module\Admin\Quote\Service\QuoteServiceCatalogManager;
use App\Module\Admin\Quote\Service\QuoteServiceFormMapper;
use App\Module\Admin\Quote\DTO\QuoteServiceFormData;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Entity\Service;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Repository\ServiceRepository;
use App\Module\Quote\Service\QuoteCalculator;
use App\Module\Quote\Service\QuotePersistence;
use App\Module\Quote\Service\QuoteService as QuoteDomainService;
use App\Module\Quote\Service\QuoteWorkflowService;
use App\Shared\Persistence\DoctrinePersistence;
use App\Shared\Validation\ConstraintViolationFormatter;
use App\Shared\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class AdminQuoteControllerBatchTest extends TestCase
{
    public function testShowDeleteAndDuplicateQuoteControllers(): void
    {
        $quote = new Quote('Q-1');
        $quote->setCustomerName('Ada')->setCustomerEmail('ada@example.com');
        $this->setId($quote, 5);

        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $quote, null, $quote);

        $show = new ShowQuoteController($quotes, new QuoteCalculator());
        self::assertSame(Response::HTTP_NOT_FOUND, $show(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $show(5)->getStatusCode());

        $service = $this->getMockBuilder(QuoteDomainService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete', 'duplicate'])
            ->getMock();
        $service->expects(self::once())->method('delete')->with($quote);
        $copy = new Quote('Q-2');
        $this->setId($copy, 8);
        $service->expects(self::once())->method('duplicate')->with($quote)->willReturn($copy);

        $delete = new DeleteQuoteController($quotes, $service);
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete(5)->getStatusCode());

        $duplicateMissingQuotes = $this->createMock(QuoteRepository::class);
        $duplicateMissingQuotes->expects(self::once())->method('find')->with(404)->willReturn(null);
        $duplicateMissing = new DuplicateQuoteController($duplicateMissingQuotes, $service, new QuoteCalculator());
        self::assertSame(Response::HTTP_NOT_FOUND, $duplicateMissing(404)->getStatusCode());

        $duplicateQuotes = $this->createMock(QuoteRepository::class);
        $duplicateQuotes->expects(self::once())->method('find')->with(5)->willReturn($quote);
        $duplicate = new DuplicateQuoteController($duplicateQuotes, $service, new QuoteCalculator());
        $payload = json_decode((string) $duplicate(5)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Q-2', $payload['data']['number']);
    }

    public function testServiceCatalogControllers(): void
    {
        $repository = $this->createMock(ServiceRepository::class);
        $service = new Service('Audit', 12000, 2000);
        $this->setId($service, 12);
        $service->setDescription('Desc')->setUnit('heure')->setDurationValue(2)->setDurationUnit('hour');

        $repository->expects(self::exactly(6))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $service, null, $service, $service, $service);
        $repository->expects(self::once())
            ->method('findBy')
            ->with([], ['title' => 'ASC'], 20, 20)
            ->willReturn([$service]);
        $repository->expects(self::once())->method('count')->with([])->willReturn(21);

        $get = new GetServiceController($repository);
        self::assertSame(Response::HTTP_NOT_FOUND, $get(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $get(12)->getStatusCode());

        $list = new ListServicesController($repository);
        $listPayload = json_decode((string) $list(new Request(['page' => '2']))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $listPayload['data']['meta']['page']);
        self::assertSame('Audit', $listPayload['data']['items'][0]['title']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Service::class));
        $entityManager->expects(self::exactly(2))->method('flush');
        $catalog = new QuoteServiceCatalogManager(new DoctrinePersistence($entityManager));
        $forms = new QuoteServiceFormMapper();

        $create = new CreateServiceController($forms, $catalog);
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $create(new Request([], ['title' => '', 'price' => '-5']))->getStatusCode()
        );
        $createdPayload = json_decode((string) $create(new Request([], [
            'title' => 'Installation',
            'description' => 'Desc',
            'unit' => 'jour',
            'durationValue' => '2',
            'durationUnit' => 'day',
            'price' => '250',
            'vatRate' => '20',
        ]))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Installation', $createdPayload['data']['title']);
        self::assertSame('2 jours', $createdPayload['data']['durationLabel']);

        $update = new UpdateServiceController($repository, $forms, $catalog);
        self::assertSame(Response::HTTP_NOT_FOUND, $update(new Request([], ['title' => 'x']), 404)->getStatusCode());
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $update(new Request([], ['title' => 'Audit', 'unit' => 'oops']), 12)->getStatusCode()
        );
        $updatedPayload = json_decode((string) $update(new Request([], [
            'title' => 'Audit premium',
            'unit' => 'jour',
            'durationValue' => '3',
            'durationUnit' => 'day',
            'price' => '300',
            'vatRate' => '5.5',
        ]), 12)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Audit premium', $updatedPayload['data']['title']);
        self::assertSame(550.0, $updatedPayload['data']['vatRate'] * 100);

        $failingEntityManager = $this->createMock(EntityManagerInterface::class);
        $failingEntityManager->expects(self::once())->method('persist')->willThrowException(new \RuntimeException('db down'));
        $failingCatalog = new QuoteServiceCatalogManager(new DoctrinePersistence($failingEntityManager));
        $failingCreate = new CreateServiceController($forms, $failingCatalog);
        self::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $failingCreate(new Request([], ['title' => 'Installation', 'price' => '250']))->getStatusCode()
        );

        $failingEntityManager2 = $this->createMock(EntityManagerInterface::class);
        $failingEntityManager2->expects(self::once())->method('flush')->willThrowException(new \RuntimeException('db down'));
        $failingCatalog2 = new QuoteServiceCatalogManager(new DoctrinePersistence($failingEntityManager2));
        $failingUpdate = new UpdateServiceController($repository, $forms, $failingCatalog2);
        self::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $failingUpdate(new Request([], ['title' => 'Audit premium', 'price' => '300']), 12)->getStatusCode()
        );
    }

    public function testUpdateQuoteStatusController(): void
    {
        $quote = new Quote('Q-9');
        $quote->setStatus(Quote::STATUS_DRAFT);
        $this->setId($quote, 9);

        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->expects(self::exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $quote);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $workflow = new QuoteWorkflowService(new QuotePersistence($entityManager));
        $validator = new DtoValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            new ConstraintViolationFormatter(),
        );

        $controller = new UpdateQuoteStatusController($quotes, new QuoteCalculator(), $workflow, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $controller(new Request(content: '{"status":"sent"}'), 404)->getStatusCode());

        $payload = json_decode((string) $controller(new Request(content: '{"status":"envoyé"}'), 9)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('sent', $payload['data']['statusCode']);
        self::assertSame('envoyé', $payload['data']['statusLabel']);
        self::assertInstanceOf(\DateTimeImmutable::class, $quote->getCreatedEmailSentAt());

        $convertedQuote = new Quote('Q-10');
        $convertedQuote->setStatus(Quote::STATUS_ACCEPTED)->setConvertedOrder(new \App\Module\Order\Entity\Order('ORD-1', $this->user()));
        $this->setId($convertedQuote, 10);

        $quotes2 = $this->createMock(QuoteRepository::class);
        $quotes2->expects(self::once())->method('find')->with(10)->willReturn($convertedQuote);
        $entityManager2 = $this->createMock(EntityManagerInterface::class);
        $entityManager2->expects(self::never())->method('flush');
        $workflow2 = new QuoteWorkflowService(new QuotePersistence($entityManager2));
        $controller2 = new UpdateQuoteStatusController($quotes2, new QuoteCalculator(), $workflow2, $validator);
        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $controller2(new Request(content: '{"status":"refused"}'), 10)->getStatusCode()
        );
    }

    public function testQuoteServiceCatalogManagerRejectsInconsistentFormData(): void
    {
        $manager = new QuoteServiceCatalogManager(new DoctrinePersistence($this->createMock(EntityManagerInterface::class)));

        try {
            $manager->create(new QuoteServiceFormData('Audit', null, null, null, null, 1000, null, true, false));
            self::fail('Expected invalid billing mode exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Mode de facturation invalide.', $exception->getMessage());
        }

        try {
            $manager->update(
                new Service('Audit', 1000, 2000),
                new QuoteServiceFormData('Audit', null, 'hour', 2, null, 1000, null, true, true)
            );
            self::fail('Expected invalid duration exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La durée doit contenir une valeur et une unité.', $exception->getMessage());
        }

        try {
            $manager->update(
                new Service('Audit', 1000, 2000),
                new QuoteServiceFormData('Audit', null, null, null, null, -1, null, false, false)
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
        self::assertSame('heure', $created->billingMode);
        self::assertNull($created->durationValue);
        self::assertNull($created->durationUnit);
        self::assertSame(1250, $created->priceCents);
        self::assertSame(0, $created->vatRateBps);
        self::assertTrue($created->updatesBillingMode);
        self::assertTrue($created->updatesDuration);

        $service = (new Service('Base', 1000, 2000))
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
        ]), $service);
        self::assertSame('Premium', $updated->title);
        self::assertSame('  ', $updated->description);
        self::assertNull($updated->billingMode);
        self::assertNull($updated->durationValue);
        self::assertSame('hour', $updated->durationUnit);
        self::assertSame(-1, $updated->priceCents);
        self::assertSame(550, $updated->vatRateBps);
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

    private function user(): \App\Module\User\Entity\User
    {
        $user = new \App\Module\User\Entity\User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
