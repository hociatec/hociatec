<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\Shared\Http\ApiExceptionSubscriber;
use App\Shared\Http\CsrfExempt;
use App\Shared\Http\RateLimitSubscriber;
use App\Shared\Http\RateLimited;
use App\Shared\Http\CsrfProtectionSubscriber;
use App\Shared\Http\CsrfTokenService;
use App\Shared\Http\RequestIdProcessor;
use App\Shared\Http\RequestIdSubscriber;
use App\Shared\Http\SecurityHeadersSubscriber;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use App\Module\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

final class HttpInfrastructureTest extends TestCase
{
    public function testRequestIdProcessorAddsRequestAndUserContextWhenAvailable(): void
    {
        $request = Request::create('https://example.test/api/orders', 'POST');
        $request->attributes->set(RequestIdSubscriber::ATTRIBUTE, 'req-123');
        $request->attributes->set('_route', 'api_orders');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $stack = new RequestStack();
        $stack->push($request);

        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 77);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $processor = new RequestIdProcessor($stack, $storage);
        $record = new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'message');
        $processed = $processor($record);

        self::assertSame('req-123', $processed->context['request_id']);
        self::assertSame('POST', $processed->context['method']);
        self::assertSame('/api/orders', $processed->context['path']);
        self::assertSame('127.0.0.1', $processed->context['ip']);
        self::assertSame('api_orders', $processed->context['route']);
        self::assertSame(77, $processed->context['user_id']);
    }

    public function testRequestIdProcessorReturnsOriginalRecordWhenNoRequestExists(): void
    {
        $processor = new RequestIdProcessor(new RequestStack());
        $record = new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'message');

        self::assertSame($record, $processor($record));
    }

    public function testRequestIdSubscriberHandlesIncomingAndGeneratedIds(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new RequestIdSubscriber();

        self::assertArrayHasKey('kernel.request', RequestIdSubscriber::getSubscribedEvents());
        self::assertArrayHasKey('kernel.response', RequestIdSubscriber::getSubscribedEvents());

        $request = Request::create('/api/orders');
        $request->headers->set(RequestIdSubscriber::HEADER, 'incoming-id');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        self::assertSame('incoming-id', $request->attributes->get(RequestIdSubscriber::ATTRIBUTE));

        $generatedRequest = Request::create('/api/orders');
        $subscriber->onKernelRequest(new RequestEvent($kernel, $generatedRequest, HttpKernelInterface::MAIN_REQUEST));
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string) $generatedRequest->attributes->get(RequestIdSubscriber::ATTRIBUTE));

        $response = new Response();
        $subscriber->onKernelResponse(new ResponseEvent($kernel, $generatedRequest, HttpKernelInterface::MAIN_REQUEST, $response));
        self::assertNotSame('', $response->headers->get(RequestIdSubscriber::HEADER, ''));

        $responseWithoutId = new Response();
        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('/api/orders'), HttpKernelInterface::MAIN_REQUEST, $responseWithoutId));
        self::assertFalse($responseWithoutId->headers->has(RequestIdSubscriber::HEADER));
    }

    public function testSecurityHeadersSubscriberAppliesHeadersOnlyToSecureApiResponses(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new SecurityHeadersSubscriber();

        self::assertArrayHasKey('kernel.response', SecurityHeadersSubscriber::getSubscribedEvents());

        $request = Request::create('https://example.test/api/orders');
        $response = new Response();
        $subscriber->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertSame('same-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        self::assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        self::assertSame('none', $response->headers->get('X-Permitted-Cross-Domain-Policies'));
        self::assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));

        $nonApiResponse = new Response();
        $subscriber->onKernelResponse(new ResponseEvent($kernel, Request::create('http://example.test/admin'), HttpKernelInterface::MAIN_REQUEST, $nonApiResponse));
        self::assertFalse($nonApiResponse->headers->has('X-Content-Type-Options'));
    }

    public function testCsrfProtectionDoesNotExemptLogoutRoute(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new CsrfProtectionSubscriber(new CsrfTokenService('test'));
        $request = Request::create('/api/auth/logout', 'POST');
        $request->attributes->set('_route', 'api_auth_logout');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    public function testCsrfProtectionAllowsExplicitControllerExemption(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new CsrfProtectionSubscriber(new CsrfTokenService('test'));
        $request = Request::create('/api/example', 'POST');
        $request->attributes->set('_controller', CsrfExemptTestController::class.'::__invoke');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testRateLimitedAttributeValidatesTokenCount(): void
    {
        $attribute = new RateLimited('public_api', 2);
        self::assertSame('public_api', $attribute->limiter);
        self::assertSame(2, $attribute->tokens);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of consumed tokens must be positive.');
        new RateLimited('public_api', 0);
    }

    public function testApiExceptionSubscriberSanitizesGenericExceptionMessages(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new ApiExceptionSubscriber($this->createMock(LoggerInterface::class));

        $domainEvent = new ExceptionEvent(
            $kernel,
            Request::create('/api/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new \DomainException('internal domain detail /home/app')
        );
        $subscriber($domainEvent);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $domainEvent->getResponse()?->getStatusCode());
        $domainPayload = json_decode((string) $domainEvent->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Requête impossible.', $domainPayload['message']);
        self::assertStringNotContainsString('/home/app', (string) $domainEvent->getResponse()?->getContent());

        $invalidArgumentEvent = new ExceptionEvent(
            $kernel,
            Request::create('/api/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new \InvalidArgumentException('internal invalid detail SQLSTATE')
        );
        $subscriber($invalidArgumentEvent);

        self::assertSame(Response::HTTP_BAD_REQUEST, $invalidArgumentEvent->getResponse()?->getStatusCode());
        $invalidArgumentPayload = json_decode((string) $invalidArgumentEvent->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Requête invalide.', $invalidArgumentPayload['message']);
        self::assertStringNotContainsString('SQLSTATE', (string) $invalidArgumentEvent->getResponse()?->getContent());
    }

    public function testAccountNotificationFormatterNormalizesInternalTargets(): void
    {
        $formatter = new \App\Module\Notification\Service\AccountNotificationFormatter();

        self::assertSame('/orders/1', $formatter->safeInternalTarget(' /orders/1 '));
        self::assertSame('/mon-espace', $formatter->safeInternalTarget('https://example.test'));
        self::assertSame('/mon-espace', $formatter->safeInternalTarget('//external'));
        self::assertSame('29/07/2026 13:45', $formatter->formatFrenchDateTime(new \DateTimeImmutable('2026-07-29T13:45:00+00:00')));
    }

    public function testRateLimitSubscriberAllowsAcceptedRequestsAndBlocksExceededOnes(): void
    {
        $subscriber = new RateLimitSubscriber(
            $this->limiters(new RateLimiterFactory(['id' => 'api_test', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'], new InMemoryStorage())),
            $this->security(null),
        );

        $controller = [new #[RateLimited('api_test', 1)] class() {
            public function __invoke(): string
            {
                return 'ok';
            }
        }, '__invoke'];
        $kernel = $this->createMock(HttpKernelInterface::class);

        $firstEvent = new ControllerEvent($kernel, $controller, Request::create('/api/orders', server: ['REMOTE_ADDR' => '127.0.0.1']), HttpKernelInterface::MAIN_REQUEST);
        $subscriber($firstEvent);
        self::assertSame($controller, $firstEvent->getController());

        $secondEvent = new ControllerEvent($kernel, $controller, Request::create('/api/orders', server: ['REMOTE_ADDR' => '127.0.0.1']), HttpKernelInterface::MAIN_REQUEST);
        $subscriber($secondEvent);

        $blockedController = $secondEvent->getController();
        self::assertIsCallable($blockedController);
        $response = $blockedController();
        self::assertSame(429, $response->getStatusCode());
        self::assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
        self::assertNotNull($response->headers->get('Retry-After'));
    }

    public function testRateLimitSubscriberLeavesControllerUntouchedWhenAttributeIsMissing(): void
    {
        $subscriber = new RateLimitSubscriber(
            $this->limiters(new RateLimiterFactory(['id' => 'api_test', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'], new InMemoryStorage())),
            $this->security(null),
        );

        $controller = static fn (): string => 'ok';
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            Request::create('/api/orders'),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $subscriber($event);
        self::assertSame($controller, $event->getController());
    }

    public function testRateLimitSubscriberUsesUserIdentifierAndRejectsUnknownLimiter(): void
    {
        $subscriber = new RateLimitSubscriber(
            $this->limiters(new \stdClass()),
            $this->security(new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female')),
        );

        $controller = [new #[RateLimited('unknown_limiter', 1)] class() {
            public function __invoke(): string
            {
                return 'ok';
            }
        }, '__invoke'];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown rate limiter "unknown_limiter".');
        $subscriber(new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            Request::create('/api/orders'),
            HttpKernelInterface::MAIN_REQUEST,
        ));
    }

    private function limiters(object $limiter): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($limiter);

        return $container;
    }

    private function security(?object $user): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}

#[CsrfExempt]
final class CsrfExemptTestController
{
    public function __invoke(): void
    {
    }
}
