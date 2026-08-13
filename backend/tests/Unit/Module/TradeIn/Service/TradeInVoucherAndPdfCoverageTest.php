<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Notification\Application\Port\AccountNotificationEventRepositoryPort;
use App\Module\TradeIn\Application\Workflow\TradeInStoreCreditVoucherIssuer;
use App\Module\TradeIn\Infrastructure\Pdf\TradeInReceiptPdfRenderer;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\LockMode;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Application\UnitOfWork;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Tests\Support\TradeInRequestFactory;
use App\Tests\Support\UserCommunicationNotifierFactory;
use App\Tests\Support\VoucherNotificationRenderingFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

final class TradeInVoucherAndPdfCoverageTest extends TestCase
{
    public function testTradeInStoreCreditVoucherIssuerIssuesVoucherAndRejectsAnonymousRequest(): void
    {
        $user = new User('issuer@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 42);
        $request = TradeInRequestFactory::submitted('TR-ISSUE-1', $user, 'Ada', 'Lovelace', 'issuer@example.test', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::exactly(2))->method('persist')->with(self::isInstanceOf(Voucher::class));
        $unitOfWork->expects(self::exactly(2))->method('flush');

        $issuer = new TradeInStoreCreditVoucherIssuer(
            new CreateVoucherHandler($unitOfWork, new VoucherPayload($this->voucherRepository())),
            $this->voucherNotificationService(),
            $unitOfWork,
            $this->createMock(LoggerInterface::class),
        );

        $voucher = $issuer->issue($request, 2000);

        self::assertSame(42, $voucher->getRecipientUserId());
        self::assertSame('issuer@example.test', $voucher->getRecipientEmail());
        self::assertSame(Voucher::TYPE_FIXED_CENTS, $voucher->getDiscountType());
        self::assertSame(2000, $voucher->getDiscountValue());
        self::assertTrue($voucher->isActive());
        self::assertStringStartsWith('Avoir de reprise TR-ISSUE-1', $voucher->getName());
        self::assertStringStartsWith('RPR-'.(new \DateTimeImmutable())->format('Ymd').'-', $voucher->getCode());

        $anonymousRequest = TradeInRequestFactory::submitted('TR-ISSUE-2', null, 'Ada', 'Lovelace', 'anonymous@example.test', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un avoir client nécessite un compte Hociatec associé à la demande.');
        $issuer->issue($anonymousRequest, 2000);
    }

    public function testTradeInStoreCreditVoucherIssuerNotificationCoversSkipSuccessAndFailure(): void
    {
        $user = new User('notify@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 55);
        $request = TradeInRequestFactory::submitted('TR-NOTIFY-1', $user, 'Ada', 'Lovelace', 'notify@example.test', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
        $voucher = new Voucher('Avoir', 'RPR-TEST', Voucher::TYPE_FIXED_CENTS, 1500);
        $this->setId($voucher, 123);

        $mailer = new class implements EmailSender {
            public ?Email $sent = null;

            public function send(Email $email): void
            {
                $this->sent = $email;
            }
        };
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->expects(self::once())->method('persist')->with($voucher);
        $unitOfWork->expects(self::once())->method('flush');

        $issuer = new TradeInStoreCreditVoucherIssuer(
            new CreateVoucherHandler($this->createMock(UnitOfWork::class), new VoucherPayload($this->voucherRepository())),
            $this->voucherNotificationService($mailer),
            $unitOfWork,
            $this->createMock(LoggerInterface::class),
        );
        $issuer->notifyIssued($request, $voucher);

        self::assertInstanceOf(\DateTimeImmutable::class, $voucher->getSentAt());
        self::assertInstanceOf(Email::class, $mailer->sent);
        self::assertStringContainsString('RPR-TEST', (string) $mailer->sent->getSubject());

        $anonymousIssuer = new TradeInStoreCreditVoucherIssuer(
            new CreateVoucherHandler($this->createMock(UnitOfWork::class), new VoucherPayload($this->voucherRepository())),
            $this->voucherNotificationService(),
            $this->createMock(UnitOfWork::class),
            $this->createMock(LoggerInterface::class),
        );
        $anonymousRequest = TradeInRequestFactory::submitted('TR-NOTIFY-2', null, 'Ada', 'Lovelace', 'notify@example.test', '0102030405', 'smartphone', 'iPhone', 100000, 2025, 'Apple', '15', 'SN', 'bon', true, true, true, 'Bon etat', null, null, 10000, 12000, new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
        $anonymousIssuer->notifyIssued($anonymousRequest, new Voucher('Avoir', 'RPR-SKIP', Voucher::TYPE_FIXED_CENTS, 1000));
        self::assertTrue(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(
            'Impossible d’envoyer l’avoir de reprise.',
            self::callback(static fn (array $context): bool => 'TR-NOTIFY-1' === $context['reference'] && $context['exception'] instanceof \RuntimeException),
        );
        $failingMailer = new class implements EmailSender {
            public function send(Email $email): void
            {
                throw new \RuntimeException('smtp down');
            }
        };
        $failingIssuer = new TradeInStoreCreditVoucherIssuer(
            new CreateVoucherHandler($this->createMock(UnitOfWork::class), new VoucherPayload($this->voucherRepository())),
            $this->voucherNotificationService($failingMailer),
            $this->createMock(UnitOfWork::class),
            $logger,
        );
        $failingIssuer->notifyIssued($request, new Voucher('Avoir', 'RPR-FAIL', Voucher::TYPE_FIXED_CENTS, 1000));
    }

    public function testTradeInReceiptPdfRendererUsesAccessibleRendererOrDompdfFallback(): void
    {
        $projectDir = sys_get_temp_dir().'/hociatec-tradein-pdf-'.bin2hex(random_bytes(4));
        mkdir($projectDir.'/bin', 0777, true);
        file_put_contents($projectDir.'/bin/render_accessible_pdf.py', "# fake\n");

        $python = $projectDir.'/fake-python'.('Windows' === PHP_OS_FAMILY ? '.bat' : '.sh');
        file_put_contents($python, 'Windows' === PHP_OS_FAMILY ? <<<'BAT'
@echo off
if "%~1"=="-c" exit /B 0
<nul set /p dummy="%PDF-accessible" > "%~3"
exit /B 0
BAT
            : <<<'SH'
#!/bin/sh
if [ "$1" = "-c" ]; then
  exit 0
fi
printf '%%PDF-accessible' > "$3"
exit 0
SH);
        chmod($python, 0755);

        $pdf = new TradeInReceiptPdfRenderer(new AccessiblePdfRenderer($projectDir, $python, ''));
        self::assertSame('%PDF-accessible', $pdf->render('<h1>Receipt</h1>'));

        $fallback = new TradeInReceiptPdfRenderer(new AccessiblePdfRenderer('/tmp/no-tradein-pdf', '', ''));
        $result = $fallback->render('<html><body><h1>Receipt</h1></body></html>');
        self::assertStringStartsWith('%PDF-', $result);
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }

    private function voucherRepository(): VoucherRepositoryPort
    {
        return new readonly class implements VoucherRepositoryPort {
            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Voucher
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

            public function findActiveForDate(\DateTimeImmutable $now): array
            {
                return [];
            }

            public function findOneByCode(?string $code): ?Voucher
            {
                return null;
            }

            public function save(Voucher $voucher): void
            {
            }

            public function findByRecipientUserId(int $userId, int $limit = 20, int $offset = 0): array
            {
                return [];
            }

            public function countByRecipientUserId(int $userId): int
            {
                return 0;
            }
        };
    }

    private function voucherNotificationService(?EmailSender $mailer = null): VoucherNotificationEmailService
    {
        $templates = new readonly class implements EmailTemplateRepositoryPort {
            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?EmailTemplate
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

            public function findForAdmin(?string $search, ?string $scenario, ?string $status, int $limit, int $offset): array
            {
                return [];
            }

            public function countForAdmin(?string $search, ?string $scenario, ?string $status): int
            {
                return 0;
            }

            public function findOneBySlug(string $slug): ?EmailTemplate
            {
                return null;
            }

            public function findActiveOneByScenarioKey(string $scenarioKey): ?EmailTemplate
            {
                return null;
            }
        };
        $notifications = new readonly class implements AccountNotificationEventRepositoryPort {
            public function findRecentForUser(User $user, int $limit = 30, int $offset = 0): array
            {
                return [];
            }

            public function countForUser(User $user): int
            {
                return 0;
            }

            public function existsForKey(string $key): bool
            {
                return false;
            }
        };
        $emailSender = $mailer ?? new class implements EmailSender {
            public function send(Email $email): void
            {
            }
        };

        return new VoucherNotificationEmailService(
            $templates,
            $emailSender,
            UserCommunicationNotifierFactory::create(
                $this,
                $notifications,
                $this->createMock(UnitOfWork::class),
                $emailSender,
                new class {
                    public function dispatch(object $message): void
                    {
                    }
                },
                $this->createMock(LoggerInterface::class),
                'no-reply@example.test',
                'https://front.example.test',
            ),
            $this->createMock(LoggerInterface::class),
            'no-reply@example.test',
            VoucherNotificationRenderingFactory::create(),
        );
    }
}
