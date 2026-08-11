<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Workflow\CustomerQuotePortalService;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Security\QuoteAccessPolicy;
use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Module\Quote\UI\Controller\Client\GetMyQuoteController;
use App\Module\Quote\UI\Controller\PublicApi\CreateQuoteController;
use App\Module\Rating\Application\Provider\PendingReviewResolver;
use App\Module\Rating\UI\Controller\ListPendingReviewsController;
use App\Module\Training\Application\Writer\TrainingWriter;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\Infrastructure\Repository\TrainingRepository;
use App\Module\Training\UI\Controller\Admin\DeleteTrainingCategoryController;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MoreLightControllerBatchesTest extends TestCase
{
    public function testPendingReviewsAndClientQuoteControllers(): void
    {
        $user = $this->user('ada@example.com');
        $resolver = $this->getMockBuilder(PendingReviewResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolve', 'count'])
            ->getMock();
        $resolver->expects(self::once())->method('resolve')->with($user, 10, 0)->willReturn([['orderItemId' => 1]]);
        $resolver->expects(self::once())->method('count')->with($user)->willReturn(1);

        $pending = new class($resolver, $user) extends ListPendingReviewsController {
            public function __construct(PendingReviewResolver $resolver, private readonly User $user)
            {
                parent::__construct($resolver);
            }

            public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };
        $pendingPayload = json_decode((string) $pending()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $pendingPayload['data']['items'][0]['orderItemId']);

        $quote = new Quote('Q-1');
        $this->setId($quote, 7);
        $quote->setCustomerEmail('ada@example.com');
        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->expects(self::exactly(3))->method('find')->willReturnOnConsecutiveCalls(null, $quote, $quote);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $workflow = new QuoteWorkflowService(new QuotePersistence($entityManager));
        $portal = new CustomerQuotePortalService(
            $quotes,
            new \App\Module\Quote\Application\Projection\QuoteFormatter(new QuoteCalculator()),
            new QuoteAccessPolicy(),
            $workflow,
        );

        $controller = new class($portal, $user) extends GetMyQuoteController {
            public function __construct(CustomerQuotePortalService $portal, private readonly User $user)
            {
                parent::__construct($portal);
            }

            public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };
        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404)->getStatusCode());
        $payload = json_decode((string) $controller(7)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Q-1', $payload['data']['number']);

        $otherUserController = new class($portal, $this->user('grace@example.com')) extends GetMyQuoteController {
            public function __construct(CustomerQuotePortalService $portal, private readonly User $user)
            {
                parent::__construct($portal);
            }

            public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
            {
                return new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($this->user);
            }
        };
        self::assertSame(Response::HTTP_NOT_FOUND, $otherUserController(7)->getStatusCode());
    }

    public function testPublicCreateQuoteAndDeleteTrainingCategoryControllers(): void
    {
        $created = new Quote('Q-9');
        $this->setId($created, 9);
        $created->setStatus(Quote::STATUS_SENT)->setCustomerName('Ada')->setCustomerEmail('ada@example.com');

        $quoteService = $this->getMockBuilder(QuoteDomainService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createFromPayload'])
            ->getMock();
        $quoteService->expects(self::once())
            ->method('createFromPayload')
            ->willReturnCallback(function (object $payload) use ($created): Quote {
                self::assertSame('sent', $payload->status);
                self::assertSame(0, $payload->shipping->cents());

                return $created;
            });

        $quoteFormatter = new \App\Module\Quote\Application\Projection\QuoteFormatter(new QuoteCalculator(), \App\Tests\Support\OrderFormatterFactory::create());
        $create = new CreateQuoteController($quoteService, $quoteFormatter, $this->validator(), new RateLimitKeyFactory(), $this->limiter(10));
        try {
            $create(new Request(content: '{"name":'));
            self::fail('Expected invalid JSON payload exception.');
        } catch (\App\Shared\Infrastructure\Http\InvalidJsonPayloadException) {
            self::assertTrue(true);
        }
        $createPayload = json_decode((string) $create(new Request(server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'customer' => ['name' => 'Ada', 'email' => 'ada@example.com'],
            'items' => [['name' => 'Diagnostic']],
            'shippingCents' => 999,
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Q-9', $createPayload['data']['number']);

        $failingQuoteService = $this->getMockBuilder(QuoteDomainService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createFromPayload'])
            ->getMock();
        $failingQuoteService->expects(self::once())
            ->method('createFromPayload')
            ->willThrowException(new \RuntimeException('db down'));
        $failingCreate = new CreateQuoteController($failingQuoteService, $quoteFormatter, $this->validator(), new RateLimitKeyFactory(), $this->limiter(10));
        self::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $failingCreate(new Request(server: ['REMOTE_ADDR' => '127.0.0.1'], content: '{"customer":{"name":"Ada","email":"ada@example.com"},"items":[{"name":"Diagnostic"}]}'))->getStatusCode()
        );

        $category = new TrainingCategory('SEO', 'seo');
        $this->setId($category, 3);
        $categories = $this->createMock(TrainingCategoryRepository::class);
        $categories->expects(self::exactly(3))->method('find')->willReturnOnConsecutiveCalls(null, $category, $category);
        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects(self::exactly(2))->method('count')->with(['category' => 'seo'])->willReturnOnConsecutiveCalls(1, 0);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($category);
        $entityManager->expects(self::once())->method('flush');
        $delete = new DeleteTrainingCategoryController($categories, $trainings, new TrainingWriter(new DoctrineUnitOfWork($entityManager)));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(404)->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $delete(3)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete(3)->getStatusCode());
    }

    private function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private function validator(): DtoValidator
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($validator, new ConstraintViolationFormatter());
    }

    private function limiter(int $limit): \Symfony\Component\RateLimiter\RateLimiterFactory
    {
        return new \Symfony\Component\RateLimiter\RateLimiterFactory([
            'id' => 'test',
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 hour',
        ], new \Symfony\Component\RateLimiter\Storage\InMemoryStorage());
    }
}
