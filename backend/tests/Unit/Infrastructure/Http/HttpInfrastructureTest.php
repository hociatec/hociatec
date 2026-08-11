<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http;

use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Exception\ApiValidationException;
use App\Shared\Application\Exception\ExternalServiceException;
use App\Shared\Application\Security\AuthenticatedUserProvider;
use App\Shared\Infrastructure\Http\ApiExceptionSubscriber;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\CsrfExempt;
use App\Shared\Infrastructure\Http\CsrfProtectionSubscriber;
use App\Shared\Infrastructure\Http\CsrfTokenService;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\JsonRequestInput;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Http\RateLimitSubscriber;
use App\Shared\Infrastructure\Http\RequestIdProcessor;
use App\Shared\Infrastructure\Http\RequestIdSubscriber;
use App\Shared\Infrastructure\Http\SecurityHeadersSubscriber;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

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
        $provider = new class($user) implements AuthenticatedUserProvider {
            public function __construct(private readonly ?User $user)
            {
            }

            public function currentUser(): ?User
            {
                return $this->user;
            }
        };

        $processor = new RequestIdProcessor($stack, $provider);
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
        $processor = new RequestIdProcessor(new RequestStack(), new class implements AuthenticatedUserProvider {
            public function currentUser(): ?User
            {
                return null;
            }
        });
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

        $errorRequest = Request::create('/api/orders');
        $errorRequest->attributes->set(RequestIdSubscriber::ATTRIBUTE, 'req-error');
        $errorResponse = ApiResponse::error('Broken');
        $subscriber->onKernelResponse(new ResponseEvent($kernel, $errorRequest, HttpKernelInterface::MAIN_REQUEST, $errorResponse));
        $errorPayload = json_decode((string) $errorResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('req-error', $errorPayload['error']['requestId']);

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

    public function testCsrfProtectionSkipsSafeSubRequestExcludedAndValidTokenCases(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $service = new CsrfTokenService('test');
        $subscriber = new CsrfProtectionSubscriber($service);
        self::assertArrayHasKey('kernel.request', CsrfProtectionSubscriber::getSubscribedEvents());

        $safeRequest = Request::create('/api/example', 'GET');
        $safeEvent = new RequestEvent($kernel, $safeRequest, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($safeEvent);
        self::assertFalse($safeEvent->hasResponse());

        $subRequest = Request::create('/api/example', 'POST');
        $subEvent = new RequestEvent($kernel, $subRequest, HttpKernelInterface::SUB_REQUEST);
        $subscriber->onKernelRequest($subEvent);
        self::assertFalse($subEvent->hasResponse());

        $excludedRequest = Request::create('/api/auth/login', 'POST');
        $excludedEvent = new RequestEvent($kernel, $excludedRequest, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($excludedEvent);
        self::assertFalse($excludedEvent->hasResponse());

        $nonApiRequest = Request::create('/admin/example', 'POST');
        $nonApiEvent = new RequestEvent($kernel, $nonApiRequest, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($nonApiEvent);
        self::assertFalse($nonApiEvent->hasResponse());

        $validRequest = Request::create('/api/example', 'POST');
        $response = new Response();
        $token = $service->issue($response, $validRequest);
        $validRequest->cookies->set(CsrfTokenService::COOKIE_NAME, $token);
        $validRequest->headers->set(CsrfTokenService::HEADER_NAME, $token);
        $validEvent = new RequestEvent($kernel, $validRequest, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($validEvent);
        self::assertFalse($validEvent->hasResponse());
    }

    public function testCsrfProtectionIgnoresUnknownOrMissingControllerMethodsForExemptionLookup(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $subscriber = new CsrfProtectionSubscriber(new CsrfTokenService('test'));

        $missingClassRequest = Request::create('/api/example', 'POST');
        $missingClassRequest->attributes->set('_controller', 'App\\Missing\\Controller::__invoke');
        $missingClassEvent = new RequestEvent($kernel, $missingClassRequest, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($missingClassEvent);
        self::assertTrue($missingClassEvent->hasResponse());

        $missingMethodRequest = Request::create('/api/example', 'POST');
        $missingMethodRequest->attributes->set('_controller', CsrfMethodMissingController::class.'::missing');
        $missingMethodEvent = new RequestEvent($kernel, $missingMethodRequest, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($missingMethodEvent);
        self::assertTrue($missingMethodEvent->hasResponse());
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

    public function testApiExceptionSubscriberCoversNonApiHttpJsonPublicAndInternalBranches(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::atLeast(1))->method('error')->with(
            'Unhandled API exception.',
            self::callback(static fn (array $context): bool => 'POST' === $context['method']
                && '/api/orders' === $context['path']
                && ($context['exception'] instanceof \RuntimeException || $context['exception'] instanceof HttpException)
                && (null === $context['request_id'] || 'req-500' === $context['request_id'])),
        );
        $subscriber = new ApiExceptionSubscriber($logger);

        $nonApiEvent = new ExceptionEvent(
            $kernel,
            Request::create('/admin/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('no-op'),
        );
        $subscriber($nonApiEvent);
        self::assertNull($nonApiEvent->getResponse());

        $jsonEvent = new ExceptionEvent(
            $kernel,
            Request::create('/api/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('wrapped', 0, new \JsonException('bad json')),
        );
        $subscriber($jsonEvent);
        self::assertSame(Response::HTTP_BAD_REQUEST, $jsonEvent->getResponse()?->getStatusCode());
        self::assertStringContainsString('Payload JSON invalide.', (string) $jsonEvent->getResponse()?->getContent());

        $http4xxEvent = new ExceptionEvent(
            $kernel,
            Request::create('/api/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new HttpException(Response::HTTP_NOT_FOUND, ''),
        );
        $subscriber($http4xxEvent);
        self::assertSame(Response::HTTP_NOT_FOUND, $http4xxEvent->getResponse()?->getStatusCode());
        $http4xxPayload = json_decode((string) $http4xxEvent->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Requête impossible.', $http4xxPayload['message']);

        $http5xxEvent = new ExceptionEvent(
            $kernel,
            Request::create('/api/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new HttpException(Response::HTTP_BAD_GATEWAY, 'upstream'),
        );
        $subscriber($http5xxEvent);
        self::assertSame(Response::HTTP_BAD_GATEWAY, $http5xxEvent->getResponse()?->getStatusCode());
        $http5xxPayload = json_decode((string) $http5xxEvent->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Une erreur interne est survenue.', $http5xxPayload['message']);

        $publicEvent = new ExceptionEvent(
            $kernel,
            Request::create('/api/orders', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            new ExternalServiceException('provider detail', 'Message public.'),
        );
        $subscriber($publicEvent);
        self::assertSame(Response::HTTP_BAD_GATEWAY, $publicEvent->getResponse()?->getStatusCode());
        $publicPayload = json_decode((string) $publicEvent->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Message public.', $publicPayload['message']);

        $internalRequest = Request::create('/api/orders', 'POST');
        $internalRequest->attributes->set(RequestIdSubscriber::ATTRIBUTE, 'req-500');
        $internalEvent = new ExceptionEvent(
            $kernel,
            $internalRequest,
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('boom'),
        );
        $subscriber($internalEvent);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $internalEvent->getResponse()?->getStatusCode());
        $internalPayload = json_decode((string) $internalEvent->getResponse()?->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Une erreur interne est survenue.', $internalPayload['message']);
    }

    public function testValidationAndInvalidJsonExceptionsExposePublicContract(): void
    {
        $validation = new ApiValidationException('Invalid payload', ['field: required']);
        self::assertSame(422, $validation->getStatusCode());
        self::assertSame('Invalid payload', $validation->publicMessage());

        $json = new InvalidJsonPayloadException('JSON invalide');
        self::assertSame(Response::HTTP_BAD_REQUEST, $json->getStatusCode());
        self::assertSame('JSON invalide', $json->publicMessage());
    }

    public function testJsonRequestInputDecodesPayloadAndRejectsInvalidFactories(): void
    {
        $request = Request::create('/api/example', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{"name":"Ada"}');
        $validInputClass = new class {
            public string $name;

            public static function fromArray(array $payload): self
            {
                $instance = new self();
                $instance->name = (string) ($payload['name'] ?? '');

                return $instance;
            }
        };

        $decoded = JsonRequestInput::decode($request, $validInputClass::class);
        self::assertSame('Ada', $decoded->name);
        self::assertSame(['name' => 'Ada'], JsonRequestInput::payload($request));

        $emptyRequest = Request::create('/api/example', 'POST', content: '');
        self::assertSame([], JsonRequestInput::optionalPayload($emptyRequest));

        try {
            $missingFactoryClass = new class {
            };
            JsonRequestInput::decode($request, $missingFactoryClass::class);
            self::fail('Expected missing fromArray exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('must expose fromArray()', $exception->getMessage());
        }

        try {
            $invalidFactoryClass = new class {
                public static function fromArray(array $payload): \stdClass
                {
                    return new \stdClass();
                }
            };
            JsonRequestInput::decode($request, $invalidFactoryClass::class);
            self::fail('Expected invalid returned instance exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('did not return an instance of itself', $exception->getMessage());
        }
    }

    public function testAccountNotificationFormatterNormalizesInternalTargets(): void
    {
        $formatter = new \App\Module\Notification\Application\Projection\AccountNotificationFormatter();

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
        $security->method('getUser')->willReturn(
            $user instanceof User ? new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user) : $user,
        );

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

final class CsrfMethodMissingController
{
    public function __invoke(): void
    {
    }
}
