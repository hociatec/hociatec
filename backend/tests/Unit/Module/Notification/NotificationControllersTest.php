<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification;

use App\Module\Notification\Application\Notification\ComputedAccountNotificationProviderInterface;
use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Module\Notification\Application\Projection\AccountNotificationFormatter;
use App\Module\Notification\Application\Provider\AccountNotificationProvider;
use App\Module\Notification\Application\Workflow\AccountNotificationReadStateService;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Application\Writer\CommunicationPreferenceUpdater;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\UI\Controller\AccountNotificationsReadStateController;
use App\Module\Notification\UI\Controller\CommunicationPreferencesController;
use App\Module\Notification\UI\Controller\ListAccountNotificationsController;
use App\Module\User\Application\Port\UserPersistencePort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class NotificationControllersTest extends TestCase
{
    public function testCommunicationPreferencesControllerCoversShowValidationSuccessAndPersistenceFailure(): void
    {
        $user = $this->user();
        $controller = new CommunicationPreferencesController(new CommunicationPreferenceUpdater(new class implements UserPersistencePort {
            public function save(User $user): void {}
            public function remove(User $user): void {}
            public function flush(): void {}
        }));
        $controller->setContainer($this->controllerContainer($user));

        $show = $this->payload($controller->show());
        self::assertSame([CommunicationPreferences::EMAIL], $show['data']['preferences']);
        self::assertNotEmpty($show['data']['choices']);

        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $controller->update($this->jsonRequest(['preferences' => []], 'PUT'))->getStatusCode(),
        );
        self::assertStringNotContainsString('Preference', (string) $controller->update($this->jsonRequest(['preferences' => []], 'PUT'))->getContent());

        $updatedResponse = $controller->update($this->jsonRequest([
            'preferences' => [CommunicationPreferences::PHONE, CommunicationPreferences::NOTIFICATION],
        ], 'PUT'));
        self::assertSame(Response::HTTP_OK, $updatedResponse->getStatusCode());
        $updated = $this->payload($updatedResponse);
        self::assertSame(
            [CommunicationPreferences::PHONE, CommunicationPreferences::NOTIFICATION],
            $updated['data']['preferences'],
        );

        $failingController = new CommunicationPreferencesController(new CommunicationPreferenceUpdater(new class implements UserPersistencePort {
            public function save(User $user): void {}
            public function remove(User $user): void {}
            public function flush(): void { throw new \RuntimeException('db down'); }
        }));
        $failingController->setContainer($this->controllerContainer($this->user()));
        self::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $failingController->update($this->jsonRequest(['preferences' => [CommunicationPreferences::EMAIL]], 'PUT'))->getStatusCode(),
        );
    }

    public function testReadStateControllerCoversShowBadPayloadSuccessAndPersistenceFailure(): void
    {
        $user = $this->user();
        $user->setAccountNotificationsSeenSignature("first\nsecond");

        $controller = new AccountNotificationsReadStateController(new AccountNotificationReadStateService(new class implements UserPersistencePort {
            public function save(User $user): void {}
            public function remove(User $user): void {}
            public function flush(): void {}
        }));
        $controller->setContainer($this->controllerContainer($user));

        $show = $this->payload($controller->show());
        self::assertSame(['first', 'second'], $show['data']['readState']['seenKeys']);

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $controller->update(Request::create('/', 'PATCH', server: [], content: '{bad'))->getStatusCode(),
        );

        $updated = $this->payload($controller->update($this->jsonRequest([
            'dismissedKey' => 'promo-1',
            'dismissedKeys' => ['promo-2'],
        ], 'PATCH')));
        self::assertContains('promo-1', $updated['data']['readState']['seenKeys']);
        self::assertContains('promo-2', $updated['data']['readState']['dismissedKeys']);

        $failingController = new AccountNotificationsReadStateController(new AccountNotificationReadStateService(new class implements UserPersistencePort {
            public function save(User $user): void {}
            public function remove(User $user): void {}
            public function flush(): void { throw new \RuntimeException('db down'); }
        }));
        $failingController->setContainer($this->controllerContainer($this->user()));
        self::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $failingController->update($this->jsonRequest(['seenSignature' => "x\ny"], 'PATCH'))->getStatusCode(),
        );
    }

    public function testListAccountNotificationsControllerCoversUnauthorizedAndPaginatedAuthenticatedPayload(): void
    {
        $anonymous = new ListAccountNotificationsController($this->notificationProvider());
        $anonymous->setContainer($this->controllerContainer(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $anonymous(Request::create('/'))->getStatusCode());

        $user = $this->user([CommunicationPreferences::EMAIL, CommunicationPreferences::NOTIFICATION]);
        $controller = new ListAccountNotificationsController($this->notificationProvider());
        $controller->setContainer($this->controllerContainer($user));

        $payload = $this->payload($controller(Request::create('/?page=1&perPage=2')));
        self::assertCount(2, $payload['data']['items']);
        self::assertSame('/mon-espace', $payload['data']['items'][1]['to']);
        self::assertSame(2, $payload['data']['meta']['total']);
    }

    private function notificationProvider(): AccountNotificationProvider
    {
        $formatter = new AccountNotificationFormatter();

        $events = new class($this->user([CommunicationPreferences::NOTIFICATION])) implements AccountNotificationEventRepositoryPort {
            public function __construct(private readonly User $user)
            {
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return null;
            }

            public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
            {
                return [];
            }

            public function count(array $criteria): int
            {
                return 0;
            }

            public function findRecentForUser(User $user, int $limit = 30, int $offset = 0): array
            {
                return [new AccountNotificationEvent($this->user, 'event-1', 'Commande', 'Prête', 'https://example.test/outside', 'order')];
            }

            public function countForUser(User $user): int
            {
                return 1;
            }

            public function existsForKey(string $key): bool
            {
                return false;
            }
        };

        $computed = new class($formatter) implements ComputedAccountNotificationProviderInterface {
            public function __construct(private readonly AccountNotificationFormatter $formatter)
            {
            }

            public function provide(User $user, \DateTimeImmutable $now): array
            {
                return [
                    $this->formatter->computedNotification('computed-1', 'Bienvenue', 'Compte activé', '/mon-compte', 'account', $now),
                ];
            }
        };

        return new AccountNotificationProvider($events, $formatter, [$computed]);
    }

    private function user(array $preferences = [CommunicationPreferences::EMAIL]): User
    {
        $user = new User('notify@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setCommunicationPreferences($preferences);
        (new \ReflectionObject($user))->getProperty('id')->setValue($user, 42);

        return $user;
    }

    private function controllerContainer(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }
}
