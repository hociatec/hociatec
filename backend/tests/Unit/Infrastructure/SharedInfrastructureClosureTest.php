<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure;

use App\Shared\Application\LockMode;
use App\Shared\Infrastructure\DateTime\DateTimeParser;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Http\ApiErrorSanitizerSubscriber;
use App\Shared\Infrastructure\Http\RequestQueryMapper;
use App\Shared\Infrastructure\Transaction\InMemoryTransactionSideEffectRegistry;
use Doctrine\DBAL\LockMode as DoctrineLockMode;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SharedInfrastructureClosureTest extends TestCase
{
    public function testDateTimeParserHandlesValidInvalidAndThrowingBranches(): void
    {
        self::assertNull(DateTimeParser::fromFormat('Y-m-d', null));
        self::assertNull(DateTimeParser::fromFormat('Y-m-d', '   '));
        self::assertNull(DateTimeParser::fromFormat('Y-m-d', '2026-02-31'));

        $date = DateTimeParser::fromFormat('Y-m-d', ' 2026-08-09 ');
        self::assertInstanceOf(\DateTimeImmutable::class, $date);
        self::assertSame('2026-08-09', $date?->format('Y-m-d'));

        self::assertSame(
            '2026-08-09',
            DateTimeParser::fromFormatOrThrow('Y-m-d', '2026-08-09', 'invalid')->format('Y-m-d'),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bad date');
        DateTimeParser::fromFormatOrThrow('Y-m-d', 'bad', 'bad date');
    }

    public function testRequestQueryMapperCoversStringNumberChoiceAndDateHelpers(): void
    {
        $request = Request::create('/api/example', 'GET', [
            'search' => '  Ada  ',
            'empty' => ' ',
            'mode' => 'DESC',
            'page' => '7',
            'zero' => '0',
            'months' => '12',
            'iso' => '2026-08-09T10:11:12+00:00',
            'natural' => '2026-08-09 10:11:12',
            'status' => 'open',
            'severity' => 'high',
            'campaignId' => '13',
            'assignedTo' => '27',
        ]);

        self::assertSame('Ada', RequestQueryMapper::string($request, 'search'));
        self::assertNull(RequestQueryMapper::nullableString($request, 'empty'));
        self::assertSame('desc', RequestQueryMapper::lowerString($request, 'mode'));
        self::assertSame('open', RequestQueryMapper::choice($request, 'status', ['open', 'closed'], 'closed'));
        self::assertSame('closed', RequestQueryMapper::choice($request, 'mode', ['open', 'closed'], 'closed'));
        self::assertSame(7, RequestQueryMapper::intOrNull($request, 'page'));
        self::assertNull(RequestQueryMapper::intOrNull($request, 'missing'));
        self::assertNull(RequestQueryMapper::requiredInt($request, 'zero'));
        self::assertSame(12, RequestQueryMapper::positiveIntFromAny($request, ['missing', 'months']));
        self::assertSame('2026-08-09T10:11:12+00:00', RequestQueryMapper::dateTime($request, 'iso')?->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-08-09 10:11:12', RequestQueryMapper::dateTime($request, 'natural')?->format('Y-m-d H:i:s'));
        self::assertSame(
            [
                'status' => 'open',
                'severity' => 'high',
                'search' => 'Ada',
                'campaignId' => 13,
                'assignedTo' => 27,
            ],
            RequestQueryMapper::betaReportFilters($request),
        );
    }

    public function testRequestQueryMapperRejectsInvalidPositiveIntegerInputs(): void
    {
        $blank = Request::create('/api/example', 'GET', ['months' => '']);
        self::assertNull(RequestQueryMapper::positiveIntFromAny($blank, ['months']));

        $invalid = Request::create('/api/example', 'GET', ['months' => 'abc']);
        try {
            RequestQueryMapper::positiveIntFromAny($invalid, ['months']);
            self::fail('Expected exception for non numeric value.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Le nombre de mois doit etre un entier positif.', $exception->getMessage());
        }

        $nonPositive = Request::create('/api/example', 'GET', ['months' => '0']);
        try {
            RequestQueryMapper::positiveIntFromAny($nonPositive, ['months']);
            self::fail('Expected exception for non positive value.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('La duree de location doit etre superieure ou egale a 1 mois.', $exception->getMessage());
        }

        $invalidDate = Request::create('/api/example', 'GET', ['at' => 'not-a-date']);
        self::assertNull(RequestQueryMapper::dateTime($invalidDate, 'at'));
    }

    public function testApiErrorSanitizerSubscriberCoversEnvironmentAndSensitivePayloadBranches(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        $devSubscriber = new ApiErrorSanitizerSubscriber('dev');
        $devResponse = new JsonResponse(['status' => 'error', 'message' => 'SQLSTATE raw', 'details' => ['trace']], 400);
        $devEvent = new ResponseEvent($kernel, Request::create('/api/test'), HttpKernelInterface::MAIN_REQUEST, $devResponse);
        $devSubscriber->onKernelResponse($devEvent);
        self::assertSame('SQLSTATE raw', json_decode((string) $devResponse->getContent(), true, 512, JSON_THROW_ON_ERROR)['message']);

        $subscriber = new ApiErrorSanitizerSubscriber('prod');
        self::assertSame(['kernel.response' => ['onKernelResponse', -70]], ApiErrorSanitizerSubscriber::getSubscribedEvents());

        $serverError = new JsonResponse(['status' => 'error', 'message' => 'SQLSTATE raw', 'details' => ['trace' => 'stack trace']], 500);
        $serverEvent = new ResponseEvent($kernel, Request::create('/api/test'), HttpKernelInterface::MAIN_REQUEST, $serverError);
        $subscriber->onKernelResponse($serverEvent);
        self::assertSame(
            ['status' => 'error', 'message' => 'Une erreur interne est survenue.', 'details' => []],
            json_decode((string) $serverError->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );

        $clientError = new JsonResponse(['status' => 'error', 'message' => 'SQLSTATE[HY000]', 'details' => ['Doctrine\\DBAL failed']], 400);
        $clientEvent = new ResponseEvent($kernel, Request::create('/api/test'), HttpKernelInterface::MAIN_REQUEST, $clientError);
        $subscriber->onKernelResponse($clientEvent);
        self::assertSame(
            ['status' => 'error', 'message' => 'Requête impossible.', 'details' => []],
            json_decode((string) $clientError->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );

        $nonApi = new JsonResponse(['status' => 'error', 'message' => 'visible', 'details' => ['ok']], 400);
        $nonApiEvent = new ResponseEvent($kernel, Request::create('/admin/test'), HttpKernelInterface::MAIN_REQUEST, $nonApi);
        $subscriber->onKernelResponse($nonApiEvent);
        self::assertSame('visible', json_decode((string) $nonApi->getContent(), true, 512, JSON_THROW_ON_ERROR)['message']);

        $success = new JsonResponse(['status' => 'ok', 'message' => 'visible'], 200);
        $successEvent = new ResponseEvent($kernel, Request::create('/api/test'), HttpKernelInterface::MAIN_REQUEST, $success);
        $subscriber->onKernelResponse($successEvent);
        self::assertSame('visible', json_decode((string) $success->getContent(), true, 512, JSON_THROW_ON_ERROR)['message']);
    }

    public function testInMemoryTransactionSideEffectRegistryCoversImmediateNestedAndRollbackBranches(): void
    {
        $logger = new CollectingLogger();
        $registry = new InMemoryTransactionSideEffectRegistry($logger);
        $events = [];

        self::assertFalse($registry->isTracking());

        $registry->afterCommit(function () use (&$events): void {
            $events[] = 'immediate';
        });
        self::assertSame(['immediate'], $events);

        $registry->afterCommit(static function (): void {
            throw new \RuntimeException('commit fail');
        });
        self::assertCount(1, $logger->errors);
        self::assertSame('Transaction side effect after commit failed.', $logger->errors[0]['message']);

        $registry->begin();
        $registry->afterRollback(function () use (&$events): void {
            $events[] = 'outer-rollback';
        });
        $registry->afterCommit(function () use (&$events): void {
            $events[] = 'outer-commit';
        });

        $registry->begin();
        $registry->afterCommit(function () use (&$events): void {
            $events[] = 'inner-commit';
        });
        $registry->afterRollback(function () use (&$events): void {
            $events[] = 'inner-rollback';
        });
        $registry->commit();
        self::assertTrue($registry->isTracking());
        self::assertSame(['immediate'], $events);

        $registry->commit();
        self::assertFalse($registry->isTracking());
        self::assertSame(['immediate', 'outer-commit', 'inner-commit'], $events);

        $registry->begin();
        $registry->afterRollback(function () use (&$events): void {
            $events[] = 'first';
        });
        $registry->afterRollback(function () use (&$events): void {
            $events[] = 'second';
        });
        $registry->rollback();

        self::assertSame(['immediate', 'outer-commit', 'inner-commit', 'second', 'first'], $events);

        $registry->rollback();
        $registry->commit();
        self::assertCount(1, $logger->errors);
    }

    public function testDoctrineLockModeMapperCoversApplicationDoctrineAndPassthroughModes(): void
    {
        self::assertSame(DoctrineLockMode::NONE, DoctrineLockModeMapper::toDoctrine(LockMode::NONE));
        self::assertSame(DoctrineLockMode::OPTIMISTIC, DoctrineLockModeMapper::toDoctrine(LockMode::OPTIMISTIC));
        self::assertSame(DoctrineLockMode::PESSIMISTIC_READ, DoctrineLockModeMapper::toDoctrine(LockMode::PESSIMISTIC_READ));
        self::assertSame(DoctrineLockMode::PESSIMISTIC_WRITE, DoctrineLockModeMapper::toDoctrine(LockMode::PESSIMISTIC_WRITE));
        self::assertSame(DoctrineLockMode::NONE, DoctrineLockModeMapper::toDoctrine(DoctrineLockMode::NONE));
        self::assertSame(123, DoctrineLockModeMapper::toDoctrine(123));
        self::assertNull(DoctrineLockModeMapper::toDoctrine(null));
    }
}

final class CollectingLogger extends AbstractLogger
{
    /** @var list<array{level:string,message:string,context:array<string,mixed>}> */
    public array $errors = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if ('error' !== $level) {
            return;
        }

        $this->errors[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
