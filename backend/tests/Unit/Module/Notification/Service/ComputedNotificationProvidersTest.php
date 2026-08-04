<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Notification\Service;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Application\Service\AppointmentStatusManager;
use App\Module\Appointment\Application\Service\AppointmentService;
use App\Module\Appointment\Application\Service\AvailabilityService;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\Audit\Application\Service\AuditMetadataFormatter;
use App\Module\Notification\Application\Service\AccountNotificationFormatter;
use App\Module\Notification\Application\Service\AppointmentNotificationProvider;
use App\Module\Notification\Application\Service\AuditNotificationProvider;
use App\Module\Notification\Application\Service\TrainingNotificationProvider;
use App\Module\Notification\Application\Service\VoucherNotificationProvider;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class ComputedNotificationProvidersTest extends TestCase
{
    public function testAppointmentNotificationProviderReturnsNextUpcomingNonCancelledAppointment(): void
    {
        $user = $this->user();
        $now = new \DateTimeImmutable('2026-07-29T10:00:00+00:00');
        $cancelled = $this->appointment($user, '2026-07-30T08:00:00+00:00');
        $cancelled->cancel();
        $next = $this->appointment($user, '2026-07-30T12:00:00+00:00');
        $later = $this->appointment($user, '2026-07-31T09:00:00+00:00');
        $this->setId($next, 12);

        $appointmentRepository = $this->createMock(AppointmentRepository::class);
        $appointmentRepository->method('findForUser')->with($user)->willReturn([$later, $cancelled, $next]);

        $provider = new AppointmentNotificationProvider($this->appointmentService($appointmentRepository), new AccountNotificationFormatter());
        $payload = $provider->provide($user, $now);

        self::assertSame('appointment:12:2026-07-30T12:00:00+00:00', $payload[0]['key']);
        self::assertSame('Prochain rendez-vous le 30/07/2026 12:00', $payload[0]['label']);
        self::assertSame('/appointments/me', $payload[0]['to']);
    }

    public function testAppointmentNotificationProviderReturnsEmptyArrayWhenNothingQualifies(): void
    {
        $user = $this->user();
        $appointmentRepository = $this->createMock(AppointmentRepository::class);
        $appointmentRepository->method('findForUser')->willReturn([]);

        $provider = new AppointmentNotificationProvider($this->appointmentService($appointmentRepository), new AccountNotificationFormatter());
        self::assertSame([], $provider->provide($user, new \DateTimeImmutable('2026-07-29T10:00:00+00:00')));
    }

    public function testAuditNotificationProviderReturnsEmptyArrayWhenUserHasNoAudit(): void
    {
        $user = $this->user();
        $repository = $this->createMock(AuditRequestRepository::class);
        $repository->method('findByUser')->with($user)->willReturn([]);

        $provider = new AuditNotificationProvider($repository, new AuditMetadataFormatter(), new AccountNotificationFormatter());
        self::assertSame([], $provider->provide($user, new \DateTimeImmutable('2026-07-29T10:00:00+00:00')));
    }

    public function testAuditNotificationProviderPrefersFirstActiveAuditAndFallsBackToDoneAudit(): void
    {
        $user = $this->user();
        $active = new AuditRequest('AUD-2', $user, AuditType::SEO, 'https://example.test', 'Goals');
        $done = new AuditRequest('AUD-1', $user, AuditType::SEO, 'https://example.test', 'Goals');
        $active->setStatus(AuditRequest::STATUS_REVIEW);
        $done->setStatus(AuditRequest::STATUS_DONE);
        $this->setId($active, 22);
        $this->setId($done, 21);

        $repository = $this->createMock(AuditRequestRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findByUser')
            ->with($user)
            ->willReturnOnConsecutiveCalls([$done, $active], [$done]);

        $provider = new AuditNotificationProvider($repository, new AuditMetadataFormatter(), new AccountNotificationFormatter());

        $payload = $provider->provide($user, new \DateTimeImmutable('2026-07-29T10:00:00+00:00'));
        self::assertSame('audit:22:review', $payload[0]['key']);
        self::assertSame('/audits/me/22', $payload[0]['to']);

        $fallback = $provider->provide($user, new \DateTimeImmutable('2026-07-29T10:00:00+00:00'));
        self::assertSame('audit:21:done', $fallback[0]['key']);
        self::assertStringContainsString('Finalisé', $fallback[0]['label']);
    }

    public function testTrainingNotificationProviderFiltersCancelledAndPastEnrollments(): void
    {
        $user = $this->user();
        $now = new \DateTimeImmutable('2026-07-29T10:00:00+00:00');
        $past = $this->trainingEnrollment($user, 'Architecture', '2026-07-28T09:00:00+00:00');
        $cancelled = $this->trainingEnrollment($user, 'DevOps', '2026-07-29T11:00:00+00:00');
        $cancelled->setStatus(TrainingEnrollment::STATUS_CANCELLED);
        $next = $this->trainingEnrollment($user, 'Cybersécurité', '2026-07-29T14:00:00+00:00');
        $this->setId($next, 45);

        $repository = $this->createMock(TrainingEnrollmentRepository::class);
        $repository->method('findForUser')->with($user)->willReturn([$cancelled, $past, $next]);

        $provider = new TrainingNotificationProvider($repository, new AccountNotificationFormatter());
        $payload = $provider->provide($user, $now);

        self::assertSame('training:45:2026-07-29T14:00:00+00:00', $payload[0]['key']);
        self::assertSame('training_reminder', $payload[0]['type']);
        self::assertSame('/trainings/me/45', $payload[0]['to']);
    }

    public function testTrainingNotificationProviderReturnsEmptyArrayWithoutFutureEnrollment(): void
    {
        $user = $this->user();
        $repository = $this->createMock(TrainingEnrollmentRepository::class);
        $repository->method('findForUser')->willReturn([
            $this->trainingEnrollment($user, 'Architecture', '2026-07-28T09:00:00+00:00'),
        ]);

        $provider = new TrainingNotificationProvider($repository, new AccountNotificationFormatter());
        self::assertSame([], $provider->provide($user, new \DateTimeImmutable('2026-07-29T10:00:00+00:00')));
    }

    public function testVoucherNotificationProviderHandlesPluralizationAndNullUserId(): void
    {
        $now = new \DateTimeImmutable('2026-07-29T10:00:00+00:00');
        $user = $this->user();
        $this->setId($user, 7);

        $active = (new Voucher('Promo 1', 'PROMO1', Voucher::TYPE_FIXED_CENTS, 1000))
            ->setStartsAt(new \DateTimeImmutable('2026-07-01T00:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-08-01T00:00:00+00:00'));
        $future = (new Voucher('Promo 2', 'PROMO2', Voucher::TYPE_FIXED_CENTS, 1000))
            ->setStartsAt(new \DateTimeImmutable('2026-08-01T00:00:00+00:00'));
        $inactive = (new Voucher('Promo 3', 'PROMO3', Voucher::TYPE_FIXED_CENTS, 1000))
            ->setIsActive(false);
        $second = new Voucher('Promo 4', 'PROMO4', Voucher::TYPE_FIXED_CENTS, 1000);
        $this->setId($active, 5);
        $this->setId($future, 6);
        $this->setId($inactive, 7);
        $this->setId($second, 4);

        $entityManager = $this->voucherEntityManager();
        foreach ([$active, $future, $inactive, $second] as $voucher) {
            $voucher->setRecipientUserId(7);
            $entityManager->persist($voucher);
        }
        $entityManager->flush();

        $provider = new VoucherNotificationProvider($this->voucherRepository($entityManager), new AccountNotificationFormatter());
        $payload = $provider->provide($user, $now);

        $expectedIds = [(int) $active->getId(), (int) $second->getId()];
        sort($expectedIds);

        self::assertSame('vouchers:'.implode(',', $expectedIds), $payload[0]['key']);
        self::assertSame('2 bons disponibles', $payload[0]['label']);
        self::assertSame('/vouchers/me', $payload[0]['to']);

        $anonymousUser = $this->user();
        self::assertSame([], $provider->provide($anonymousUser, $now));
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function appointment(User $user, string $startAt): Appointment
    {
        return new Appointment($user, new Prestation('Diagnostic', 60, 9000), new \DateTimeImmutable($startAt));
    }

    private function trainingEnrollment(User $user, string $title, string $startsAt): TrainingEnrollment
    {
        $training = new Training($title, strtolower($title), 60, 1000);
        $session = new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable($startsAt),
            new \DateTimeImmutable($startsAt.' +2 hours'),
            12,
        );

        return new TrainingEnrollment($session, $user, 1000);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private function appointmentService(AppointmentRepository $appointmentRepository): AppointmentService
    {
        return new AppointmentService(
            $appointmentRepository,
            $this->createMock(WorkingDayConfigurationRepository::class),
            new AvailabilityService(
                $this->createMock(WorkingDayConfigurationRepository::class),
                $appointmentRepository,
            ),
            new AppointmentStatusManager(new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class))),
            new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)),
            new DoctrineTransactionManager($this->createMock(EntityManagerInterface::class)),
        );
    }

    private function voucherEntityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema([
            $entityManager->getClassMetadata(Voucher::class),
        ]);

        return $entityManager;
    }

    private function voucherRepository(EntityManager $entityManager): VoucherRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new VoucherRepository($registry);
    }
}
