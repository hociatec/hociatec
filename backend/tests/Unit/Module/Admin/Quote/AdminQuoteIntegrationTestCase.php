<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Quote;

use App\Module\Admin\Application\Quote\Applier\QuoteServiceFormApplier;
use App\Module\Admin\Application\Quote\Handler\CreateQuoteServiceHandler;
use App\Module\Admin\Application\Quote\Handler\UpdateQuoteServiceHandler;
use App\Module\Admin\UI\Quote\Mapper\QuoteServiceFormMapper;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Factory\QuoteNumberGenerator;
use App\Module\Quote\Application\Workflow\QuoteEmailService;
use App\Module\Quote\Application\Workflow\QuoteService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Service\Domain\Entity\ServiceOffering;
use App\Module\Quote\Infrastructure\Pdf\QuotePdfService;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\Service\Infrastructure\Repository\ServiceOfferingRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Application\Mail\EmailSender;
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
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AdminQuoteIntegrationTestCase extends TestCase
{
    protected function failingQuoteService(EntityManager $em): QuoteService
    {
        $persistence = new DoctrineUnitOfWork($em);
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();

        return new class($persistence, new QuoteNumberGenerator(new QuoteRepository($this->registry($em))), new QuoteCalculator(), new \App\Module\Quote\Application\Mapper\QuoteHydrator($persistence, new \App\Module\Quote\Application\Factory\QuoteItemFactory($productRepository))) extends QuoteService {
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

    protected function throwingCreateQuoteService(): CreateQuoteServiceHandler
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willThrowException(new \RuntimeException('doctrine down'));

        return new CreateQuoteServiceHandler(new DoctrineUnitOfWork($entityManager), new QuoteServiceFormApplier());
    }

    protected function throwingUpdateQuoteService(): UpdateQuoteServiceHandler
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException(new \RuntimeException('doctrine down'));

        return new UpdateQuoteServiceHandler(new DoctrineUnitOfWork($entityManager), new QuoteServiceFormApplier());
    }

    protected function throwingValidator(): DtoValidator
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willThrowException(new \LogicException('validator down'));

        return new DtoValidator($validator, new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $override */
    protected function quotePayload(array $override = []): array
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

    protected function quoteService(EntityManager $em): QuoteService
    {
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $productRepository->method('find')->willReturn(null);

        $persistence = new DoctrineUnitOfWork($em);

        return new QuoteService(
            $persistence,
            new QuoteNumberGenerator(new QuoteRepository($this->registry($em))),
            new QuoteCalculator(),
            new \App\Module\Quote\Application\Mapper\QuoteHydrator(
                $persistence,
                new \App\Module\Quote\Application\Factory\QuoteItemFactory($productRepository),
                new \DateTimeImmutable('2026-08-01'),
            ),
        );
    }

    protected function emailService(EntityManager $em, bool $fail = false): QuoteEmailService
    {
        unset($fail);

        return new QuoteEmailService(
            new DoctrineUnitOfWork($em),
            new Outbox(new DoctrineUnitOfWork($em)),
            new UserRepository($this->registry($em)),
            \App\Tests\Support\UserCommunicationNotifierFactory::create(
                $this,
                new AccountNotificationEventRepository($this->registry($em)),
                new DoctrineUnitOfWork($em),
                $this->createMock(EmailSender::class),
                $this->createMock(MessageBusInterface::class),
                $this->createMock(LoggerInterface::class),
                'noreply@example.test',
                'https://front.example.test',
            ),
        );
    }

    protected function pdfService(): QuotePdfService
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

    protected function product(): Product
    {
        $product = new Product('Phone', 'phone', 'SKU-PHONE', 'Desc', 9900, 10, new Category('Phones', 'phones'));
        (new \ReflectionObject($product))->getProperty('id')->setValue($product, 2);

        return $product;
    }

    protected function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    protected function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    protected function payload(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
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

    protected function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    protected function quoteRepository(EntityManager $em): QuoteRepository
    {
        return new QuoteRepository($this->registry($em));
    }

    protected function serviceRepository(EntityManager $em): ServiceOfferingRepository
    {
        return new ServiceOfferingRepository($this->registry($em));
    }

    protected function quoteFormatter(): \App\Module\Quote\Application\Projection\QuoteFormatter
    {
        return new \App\Module\Quote\Application\Projection\QuoteFormatter(
            new QuoteCalculator(),
            \App\Tests\Support\OrderFormatterFactory::create(),
        );
    }

    protected function serviceFormatter(): \App\Module\Service\Application\Projection\ServiceFormatter
    {
        return new \App\Module\Service\Application\Projection\ServiceFormatter();
    }
}
