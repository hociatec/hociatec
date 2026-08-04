<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Support\Service;

use App\Module\Admin\UI\Operations\Controller\SupportOperationsController;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Service\AdminOperationsFormatter;
use App\Module\Admin\Application\Operations\Service\OperationsPersistence;
use App\Module\Admin\Application\Operations\Service\SupportOperationsService;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Service\UserCommunicationNotifier;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Infrastructure\Repository\OrderEventRepository;
use App\Module\Order\Infrastructure\Repository\OrderRepository;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\DTO\SupportUpdateData;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Infrastructure\Repository\SupportRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\User\Application\Service\AdminCustomerEmailService;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
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

final class SupportOperationsServiceTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testServiceListsCreatesUpdatesAndRepliesToSupportRequests(): void
    {
        [$customer, $order] = $this->seedCustomerAndOrder();
        $service = $this->supportService();
        $customerId = $customer->getId();
        $orderId = $order->getId();
        self::assertNotNull($customerId);
        self::assertNotNull($orderId);

        $created = $service->create(new SupportCreateData($customerId, '  Produit casse  ', ' damaged ', '  contexte  ', '  note interne  ', $orderId));
        self::assertSame('Produit casse', $created['subject']);
        self::assertSame('damaged', $created['reason']);
        self::assertSame('contexte', $created['message']);
        self::assertSame('note interne', $created['internalNotes']);
        self::assertSame($customerId, $created['customer']['id']);
        self::assertSame($orderId, $created['order']['id']);

        $supportId = $created['id'];
        $listed = $service->list();
        self::assertCount(1, $listed);
        self::assertSame($supportId, $listed[0]['id']);

        $updated = $service->update($supportId, new SupportUpdateData(SupportRequest::STATUS_IN_PROGRESS, '  nouvelle note  ', '  Nouveau sujet  '));
        self::assertSame(SupportRequest::STATUS_IN_PROGRESS, $updated['status']);
        self::assertSame('Nouveau sujet', $updated['subject']);
        self::assertSame('nouvelle note', $updated['internalNotes']);

        $replied = $service->reply($supportId, new SupportReplyData('  Bonjour client  ', null, null));
        self::assertSame(SupportRequest::STATUS_WAITING_CUSTOMER, $replied['status']);
        self::assertStringContainsString('Reponse envoy', $this->removeAccents((string) $replied['internalNotes']));

        $resolved = $service->reply($supportId, new SupportReplyData('  Cloture  ', '  Resolution  ', SupportRequest::STATUS_RESOLVED));
        self::assertSame(SupportRequest::STATUS_RESOLVED, $resolved['status']);
        self::assertNotNull($resolved['resolvedAt']);
    }

    public function testServiceRejectsMissingResourcesInvalidStatusAndEmptyReply(): void
    {
        [$customer] = $this->seedCustomerAndOrder();
        $service = $this->supportService();
        $customerId = $customer->getId();
        self::assertNotNull($customerId);

        try {
            $service->create(new SupportCreateData(404, 'Sujet', 'other', 'message', null, null));
            self::fail('Missing customer did not throw.');
        } catch (OperationsResourceNotFoundException $exception) {
            self::assertSame('Client introuvable.', $exception->getMessage());
        }

        $created = $service->create(new SupportCreateData($customerId, 'Sujet', 'other', 'message', null, null));
        $supportId = $created['id'];

        $this->expectInvalidArgument('Statut de support invalide.', fn () => $service->update($supportId, new SupportUpdateData('bad-status', null, null)));
        $this->expectInvalidArgument('Le message de r', fn () => $service->reply($supportId, new SupportReplyData('  ', null, null)));

        try {
            $service->update(404, new SupportUpdateData(null, null, null));
            self::fail('Missing support did not throw.');
        } catch (OperationsResourceNotFoundException $exception) {
            self::assertSame('Demande SAV introuvable.', $exception->getMessage());
        }
    }

    public function testControllerMapsSupportOperationsResponses(): void
    {
        [$customer] = $this->seedCustomerAndOrder();
        $controller = new SupportOperationsController($this->supportService(), $this->dtoValidator());

        $list = $controller->list();
        self::assertSame(Response::HTTP_OK, $list->getStatusCode());

        $created = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'customerId' => $customer->getId(),
            'subject' => 'SAV',
            'reason' => 'other',
            'message' => 'Message',
        ], JSON_THROW_ON_ERROR)));
        $createdPayload = $this->json($created);
        self::assertSame(Response::HTTP_CREATED, $created->getStatusCode());

        $supportId = $createdPayload['data']['item']['id'];
        $updated = $controller->update($supportId, new Request([], [], [], [], [], [], '{"status":"in_progress","internalNotes":"A traiter"}'));
        self::assertSame(Response::HTTP_OK, $updated->getStatusCode());

        $reply = $controller->reply($supportId, new Request([], [], [], [], [], [], '{"message":"Reponse","subject":"Suite"}'));
        $replyPayload = $this->json($reply);
        self::assertSame(Response::HTTP_OK, $reply->getStatusCode());
        self::assertTrue($replyPayload['data']['sent']);

        self::assertSame(Response::HTTP_NOT_FOUND, $controller->update(404, new Request([], [], [], [], [], [], '{"status":"resolved"}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->reply($supportId, new Request([], [], [], [], [], [], '{"message":"   "}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller->create(new Request([], [], [], [], [], [], '{bad json'))->getStatusCode());
    }

    /** @return array{User,Order} */
    private function seedCustomerAndOrder(): array
    {
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $order = new Order('ORD-SAV-1', $user);

        $this->entityManager()->persist($user);
        $this->entityManager()->persist($order);
        $this->entityManager()->flush();

        return [$user, $order];
    }

    private function supportService(): SupportOperationsService
    {
        $entityManager = $this->entityManager();

        return new SupportOperationsService(
            $this->repository(SupportRequestRepository::class),
            $this->repository(UserRepository::class),
            $this->repository(OrderRepository::class),
            $this->customerEmailService(),
            new OperationsPersistence($entityManager),
            new AdminOperationsFormatter($this->repository(OrderRepository::class), $this->repository(OrderEventRepository::class)),
        );
    }

    private function customerEmailService(): AdminCustomerEmailService
    {
        $notifier = new UserCommunicationNotifier(
            $this->repository(AccountNotificationEventRepository::class),
            new DoctrineUnitOfWork($this->entityManager()),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'contact@example.test',
            'https://example.test',
        );

        return new AdminCustomerEmailService(
            $this->createMock(MailerInterface::class),
            $this->createMock(LoggerInterface::class),
            $notifier,
            'contact@example.test',
        );
    }

    private function dtoValidator(): DtoValidator
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($validator, new ConstraintViolationFormatter());
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Order::class),
            $entityManager->getClassMetadata(OrderEvent::class),
            $entityManager->getClassMetadata(SupportRequest::class),
            $entityManager->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     *
     * @return T
     */
    private function repository(string $repositoryClass): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new $repositoryClass($registry);
    }

    /** @param \Closure(): mixed $callback */
    private function expectInvalidArgument(string $messagePart, \Closure $callback): void
    {
        try {
            $callback();
            self::fail('InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString($messagePart, $exception->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function json(\Symfony\Component\HttpFoundation\JsonResponse $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function removeAccents(string $value): string
    {
        return strtr($value, [
            "\xC3\xA9" => 'e',
            "\xC3\xA8" => 'e',
            "\xC3\xAA" => 'e',
            "\xC3\xA0" => 'a',
        ]);
    }
}
