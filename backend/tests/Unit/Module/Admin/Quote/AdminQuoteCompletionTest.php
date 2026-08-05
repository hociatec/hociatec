<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Quote;

use App\Module\Admin\UI\Quote\Controller\AddProductItemController;
use App\Module\Admin\UI\Quote\Controller\CreateQuoteController;
use App\Module\Admin\UI\Quote\Controller\CreateServiceController;
use App\Module\Admin\UI\Quote\Controller\DeleteQuoteController;
use App\Module\Admin\UI\Quote\Controller\DeleteServiceController;
use App\Module\Admin\UI\Quote\Controller\DuplicateQuoteController;
use App\Module\Admin\UI\Quote\Controller\GeneratePdfController;
use App\Module\Admin\UI\Quote\Controller\GetServiceController;
use App\Module\Admin\UI\Quote\Controller\ListQuoteMetadataController;
use App\Module\Admin\UI\Quote\Controller\ListQuotesController;
use App\Module\Admin\UI\Quote\Controller\ListServicesController;
use App\Module\Admin\UI\Quote\Controller\SendQuoteEmailController;
use App\Module\Admin\UI\Quote\Controller\ShowQuoteController;
use App\Module\Admin\UI\Quote\Controller\UpdateQuoteController;
use App\Module\Admin\UI\Quote\Controller\UpdateQuoteStatusController;
use App\Module\Admin\UI\Quote\Controller\UpdateServiceController;
use App\Module\Admin\Application\Quote\Handler\CreateQuoteServiceHandler;
use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\Mapper\QuoteServiceFormMapper;
use App\Module\Admin\Application\Quote\Handler\UpdateQuoteServiceHandler;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Domain\Entity\ServiceOffering as ServiceOffering;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\Quote\Infrastructure\Repository\ServiceOfferingRepository;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Workflow\QuoteEmailService;
use App\Module\Quote\Application\Factory\QuoteNumberGenerator;
use App\Module\Quote\Infrastructure\Pdf\QuotePdfService;
use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use App\Module\Quote\Application\Workflow\QuoteService;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Outbox\Application\Outbox;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdminQuoteCompletionTest extends TestCase
{
    public function testAdminQuoteAndServiceControllers(): void
    {
        $em = $this->entityManager();
        $quoteRepository = new QuoteRepository($this->registry($em));
        $serviceRepository = new ServiceOfferingRepository($this->registry($em));
        $calculator = new QuoteCalculator();
        $quoteService = $this->quoteService($em);
        $emailService = $this->emailService($em);
        $validator = $this->validator(11);

        $formApplier = new QuoteServiceFormApplier();
        $createQuoteService = new CreateQuoteServiceHandler(new DoctrineUnitOfWork($em), $formApplier);
        $updateQuoteService = new UpdateQuoteServiceHandler(new DoctrineUnitOfWork($em), $formApplier);
        $formMapper = new QuoteServiceFormMapper();
        $createService = new CreateServiceController($formMapper, $createQuoteService);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $createService(Request::create('/', 'POST', ['title' => '', 'price' => 10]))->getStatusCode());
        $createdService = $createService(Request::create('/', 'POST', ['title' => 'Audit', 'description' => 'Desc', 'unit' => 'jour', 'durationValue' => '2', 'durationUnit' => 'day', 'price' => '120,50', 'vatRate' => '20']));
        self::assertSame(Response::HTTP_CREATED, $createdService->getStatusCode());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new CreateServiceController($formMapper, $this->throwingCreateQuoteService()))(Request::create('/', 'POST', ['title' => 'Down', 'price' => 10]))->getStatusCode());
        $serviceId = (int) $this->payload($createdService)['data']['id'];

        self::assertSame(Response::HTTP_OK, (new ListServicesController($serviceRepository))(Request::create('/?page=1&perPage=5'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new GetServiceController($serviceRepository))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new GetServiceController($serviceRepository))($serviceId)->getStatusCode());

        $updateService = new UpdateServiceController($serviceRepository, $formMapper, $updateQuoteService);
        self::assertSame(Response::HTTP_NOT_FOUND, $updateService(Request::create('/', 'POST', ['title' => 'x']), 999)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $updateService(Request::create('/', 'POST', ['unit' => 'bogus']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $updateService(Request::create('/', 'POST', ['durationValue' => '2', 'durationUnit' => '']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $updateService(Request::create('/', 'POST', ['title' => 'Audit', 'price' => 'abc']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $updateService(Request::create('/', 'POST', ['title' => 'Audit updated', 'price' => '90', 'durationValue' => '1', 'durationUnit' => 'hour']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $updateService(Request::create('/', 'POST', ['title' => 'Audit partial']), $serviceId)->getStatusCode());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new UpdateServiceController($serviceRepository, $formMapper, $this->throwingUpdateQuoteService()))(Request::create('/', 'POST', ['title' => 'Down']), $serviceId)->getStatusCode());

        $createQuote = new CreateQuoteController($quoteService, $calculator, $emailService, $validator);
        $createdQuote = $createQuote($this->jsonRequest($this->quotePayload()));
        self::assertSame(Response::HTTP_CREATED, $createdQuote->getStatusCode());
        $quoteId = (int) $this->payload($createdQuote)['data']['id'];
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new CreateQuoteController($this->failingQuoteService($em), $calculator, $emailService, $validator))($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'Fail']])))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, (new CreateQuoteController($quoteService, $calculator, $this->emailService($em, true), $validator))($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'Email fail', 'email' => 'email-fail@example.test']])))->getStatusCode());

        self::assertSame(Response::HTTP_OK, (new ListQuoteMetadataController())()->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ListQuotesController($quoteRepository, $calculator))(Request::create('/?q=ada&status=draft'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new ShowQuoteController($quoteRepository, $calculator))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ShowQuoteController($quoteRepository, $calculator))($quoteId)->getStatusCode());

        $updateQuote = new UpdateQuoteController($quoteRepository, $quoteService, $calculator, $emailService, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $updateQuote($this->jsonRequest($this->quotePayload(), 'PUT'), 999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $updateQuote($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'Grace', 'email' => 'grace@example.test']]), 'PUT'), $quoteId)->getStatusCode());
        $quoteRepository->find($quoteId)?->setCreatedEmailSentAt(null);
        self::assertSame(Response::HTTP_OK, (new UpdateQuoteController($quoteRepository, $quoteService, $calculator, $this->emailService($em, true), $validator))($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'No mail', 'email' => 'no-mail@example.test']]), 'PUT'), $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new UpdateQuoteController($quoteRepository, $this->failingQuoteService($em), $calculator, $emailService, $validator))($this->jsonRequest($this->quotePayload(), 'PUT'), $quoteId)->getStatusCode());

        $product = $this->product();
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $productRepository->method('find')->willReturnMap([[1, null], [2, $product]]);
        $addProduct = new AddProductItemController(new QuoteWorkflowService(new QuotePersistence($em)), $quoteRepository, $productRepository, $calculator, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $addProduct($this->jsonRequest(['productId' => 2]), 999)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $addProduct($this->jsonRequest(['productId' => 1]), $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $addProduct($this->jsonRequest(['productId' => 2, 'quantity' => 2, 'vatRate' => 20]), $quoteId)->getStatusCode());

        $status = new UpdateQuoteStatusController($quoteRepository, $calculator, new QuoteWorkflowService(new QuotePersistence($em)), $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $status($this->jsonRequest(['status' => Quote::STATUS_SENT], 'PATCH'), 999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $status($this->jsonRequest(['status' => Quote::STATUS_SENT], 'PATCH'), $quoteId)->getStatusCode());
        $quote = $quoteRepository->find($quoteId);
        self::assertInstanceOf(Quote::class, $quote);
        $quote->setConvertedOrder(new Order('ADM-ORDER-1', new User('converted@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female')));
        self::assertSame(Response::HTTP_BAD_REQUEST, $status($this->jsonRequest(['status' => Quote::STATUS_REFUSED], 'PATCH'), $quoteId)->getStatusCode());
        $quote->setConvertedOrder(null);

        $send = new SendQuoteEmailController($quoteRepository, $emailService, new QuoteWorkflowService(new QuotePersistence($em)), $this->createMock(LoggerInterface::class), $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $send($this->jsonRequest(['to' => 'client@example.test']), '0')->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $send($this->jsonRequest(['to' => 'client@example.test']), '999')->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $send(Request::create('/', 'POST', server: [], content: '{bad'), (string) $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $send($this->jsonRequest(['to' => 'bad']), (string) $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, (new SendQuoteEmailController($quoteRepository, $emailService, new QuoteWorkflowService(new QuotePersistence($em)), $this->createMock(LoggerInterface::class), $this->throwingValidator()))($this->jsonRequest(['to' => 'unexpected@example.test']), (string) $quoteId)->getStatusCode());
        $client = new User('client@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $client->setPassword('hashed')->setCommunicationPreferences([]);
        $em->persist($client);
        $em->flush();
        $sentResponse = $send($this->jsonRequest(['to' => 'external-client@example.test']), (string) $quoteId);
        self::assertSame(Response::HTTP_OK, $sentResponse->getStatusCode(), (string) $sentResponse->getContent());

        $pdf = new GeneratePdfController($quoteRepository, $calculator, $this->pdfService(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        self::assertSame(Response::HTTP_NOT_FOUND, $pdf(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $pdf($quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_IMPLEMENTED, (new GeneratePdfController($quoteRepository, $calculator, new class extends QuotePdfService {
            public function __construct()
            {
            }

            public function render(Quote $quote, array $totals): string
            {
                throw new \RuntimeException('pdf down');
            }
        }, new \App\Shared\Infrastructure\Http\AttachmentResponseFactory()))($quoteId)->getStatusCode());

        self::assertSame(Response::HTTP_NOT_FOUND, (new DuplicateQuoteController($quoteRepository, $quoteService, $calculator))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new DuplicateQuoteController($quoteRepository, $quoteService, $calculator))($quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new DeleteQuoteController($quoteRepository, $quoteService))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new DeleteQuoteController($quoteRepository, $quoteService))($quoteId)->getStatusCode());

        $deleteService = new DeleteServiceController($serviceRepository, new \App\Module\Admin\Application\Quote\Handler\DeleteQuoteServiceHandler(
            $serviceRepository,
            new \App\Module\Quote\Infrastructure\Persistence\QuotePersistence($em),
        ));
        self::assertSame(Response::HTTP_NOT_FOUND, $deleteService(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $deleteService($serviceId)->getStatusCode());
    }

    private function failingQuoteService(EntityManager $em): QuoteService
    {
        return new class(new QuotePersistence($em), $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock(), new QuoteNumberGenerator(new QuoteRepository($this->registry($em))), new QuoteCalculator()) extends QuoteService {
            public function createFromPayload(\App\Module\Quote\Application\DTO\QuotePayload $payload): Quote
            {
                throw new \RuntimeException('quote down');
            }

            public function updateFromPayload(Quote $quote, \App\Module\Quote\Application\DTO\QuotePayload $payload): Quote
            {
                throw new \RuntimeException('quote down');
            }
        };
    }

    private function throwingCreateQuoteService(): CreateQuoteServiceHandler
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willThrowException(new \RuntimeException('doctrine down'));

        return new CreateQuoteServiceHandler(new DoctrineUnitOfWork($entityManager), new QuoteServiceFormApplier());
    }

    private function throwingUpdateQuoteService(): UpdateQuoteServiceHandler
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException(new \RuntimeException('doctrine down'));

        return new UpdateQuoteServiceHandler(new DoctrineUnitOfWork($entityManager), new QuoteServiceFormApplier());
    }

    private function throwingValidator(): DtoValidator
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willThrowException(new \LogicException('validator down'));

        return new DtoValidator($validator, new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $override */
    private function quotePayload(array $override = []): array
    {
        return $override + [
            'customer' => ['name' => 'Ada', 'email' => 'ada@example.test', 'company' => 'Hociatec', 'address' => 'Paris'],
            'status' => Quote::STATUS_DRAFT,
            'discountCents' => 100,
            'shippingCents' => 200,
            'conditions' => 'Conditions',
            'validFrom' => '2026-08-01',
            'validUntil' => '2026-08-31',
            'items' => [['name' => 'Audit', 'unitPriceCents' => 10000, 'quantity' => 1, 'vatRateBps' => 2000]],
        ];
    }

    private function quoteService(EntityManager $em): QuoteService
    {
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $productRepository->method('find')->willReturn(null);

        return new QuoteService(
            new QuotePersistence($em),
            $productRepository,
            new QuoteNumberGenerator(new QuoteRepository($this->registry($em))),
            new QuoteCalculator(),
            new \DateTimeImmutable('2026-08-01'),
        );
    }

    private function emailService(EntityManager $em, bool $fail = false): QuoteEmailService
    {
        unset($fail);

        return new QuoteEmailService(
            new QuotePersistence($em),
            new Outbox(new DoctrineUnitOfWork($em)),
            new UserRepository($this->registry($em)),
            new UserCommunicationNotifier(
                new AccountNotificationEventRepository($this->registry($em)),
                new DoctrineUnitOfWork($em),
                $this->createMock(MailerInterface::class),
                $this->createMock(MessageBusInterface::class),
                $this->createMock(LoggerInterface::class),
                'noreply@example.test',
                'https://front.example.test',
            ),
        );
    }

    private function pdfService(): QuotePdfService
    {
        return new class extends QuotePdfService {
            public function __construct()
            {
            }

            public function render(Quote $quote, array $totals): string
            {
                return '%PDF-admin-quote';
            }
        };
    }

    private function product(): Product
    {
        $product = new Product('Phone', 'phone', 'SKU-PHONE', 'Desc', 9900, 10, new Category('Phones', 'phones'));
        (new \ReflectionObject($product))->getProperty('id')->setValue($product, 2);

        return $product;
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(Quote::class),
            $em->getClassMetadata(QuoteItem::class),
            $em->getClassMetadata(ServiceOffering::class),
            $em->getClassMetadata(OutboxEvent::class),
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $em;
    }

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function projectDir(): string
    {
        $dir = sys_get_temp_dir().'/hociatec-admin-quote-tests';
        if (!is_dir($dir.'/bin')) {
            mkdir($dir.'/bin', 0777, true);
        }
        if (!is_file($dir.'/bin/render_accessible_pdf.py')) {
            file_put_contents($dir.'/bin/render_accessible_pdf.py', '# fake');
        }

        return $dir;
    }

    private function fakePython(): string
    {
        $path = $this->projectDir().'/fake-python.bat';
        if (!is_file($path)) {
            file_put_contents($path, "@echo off\r\nif \"%1\"==\"-c\" exit /b 0\r\necho %%PDF-test > \"%4\"\r\nexit /b 0\r\n");
        }

        return $path;
    }
}
