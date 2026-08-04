<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Service;

use App\Module\Appointment\Application\DTO\WorkingDayData;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Application\Service\AppointmentFormatter;
use App\Module\Appointment\Application\Service\AppointmentService;
use App\Module\Appointment\Application\Service\AppointmentStatusWorkflow;
use App\Module\Appointment\Application\Service\AvailabilityService;
use App\Module\Appointment\Application\Service\ChangeAppointmentStatusHandler;
use App\Module\Appointment\Application\Service\PrestationPersistence;
use App\Module\Appointment\Application\Service\PrestationService;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationPersistence;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationService;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AppointmentServicesTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private DoctrineUnitOfWork $persistence;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('wrapInTransaction')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $this->persistence = new DoctrineUnitOfWork($this->entityManager);
    }

    public function testStatusWorkflowExposesKnownStatusesAndLabels(): void
    {
        $workflow = new AppointmentStatusWorkflow();

        self::assertSame(
            [Appointment::STATUS_CONFIRMED, Appointment::STATUS_CANCELLED],
            $workflow->knownStatuses(),
        );
        self::assertSame('Confirmé', $workflow->label(Appointment::STATUS_CONFIRMED));
        self::assertSame('Unknown', $workflow->label('unknown'));
        self::assertTrue($workflow->isKnownStatus('CONFIRMED'));
        self::assertFalse($workflow->isKnownStatus('pending'));
    }

    public function testStatusWorkflowAndHandlerHandleTransitionsAndFlushOnChange(): void
    {
        $appointment = $this->createFutureAppointment();
        $workflow = new AppointmentStatusWorkflow();
        $handler = new ChangeAppointmentStatusHandler($workflow, $this->persistence);

        self::assertTrue($workflow->canBeCancelled($appointment));
        self::assertTrue($workflow->canTransition($appointment, Appointment::STATUS_CANCELLED));
        self::assertFalse($workflow->canTransition($appointment, Appointment::STATUS_CONFIRMED));
        self::assertFalse($workflow->canTransition($appointment, 'invalid'));

        $this->entityManager->expects(self::once())->method('flush');
        $handler->change($appointment, Appointment::STATUS_CANCELLED);

        self::assertSame(Appointment::STATUS_CANCELLED, $appointment->getStatus());
    }

    public function testStatusHandlerRejectsPastCancellationAndInvalidTransitions(): void
    {
        $workflow = new AppointmentStatusWorkflow();
        $handler = new ChangeAppointmentStatusHandler($workflow, $this->persistence);
        $pastAppointment = $this->createAppointmentAt(new \DateTimeImmutable('2026-01-10T09:00:00+00:00'));

        self::assertFalse($workflow->canBeCancelled($pastAppointment));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transition de statut impossible pour ce rendez-vous.');

        $handler->change($pastAppointment, Appointment::STATUS_CANCELLED);
    }

    public function testStatusHandlerRejectsUnknownStatusAndNoopsOnSameStatus(): void
    {
        $handler = new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $this->persistence);
        $appointment = $this->createFutureAppointment();

        $this->entityManager->expects(self::never())->method('flush');
        $handler->change($appointment, Appointment::STATUS_CONFIRMED);
        self::assertSame(Appointment::STATUS_CONFIRMED, $appointment->getStatus());

        try {
            $handler->change($appointment, 'draft');
            self::fail('Expected exception was not thrown.');
        } catch (\DomainException $exception) {
            self::assertSame('Statut de rendez-vous inconnu.', $exception->getMessage());
        }
    }

    public function testStatusHandlerAllowsReopeningCancelledAppointment(): void
    {
        $appointment = $this->createFutureAppointment();
        $appointment->setStatus(Appointment::STATUS_CANCELLED);
        $workflow = new AppointmentStatusWorkflow();
        $handler = new ChangeAppointmentStatusHandler($workflow, $this->persistence);

        self::assertTrue($workflow->canTransition($appointment, Appointment::STATUS_CONFIRMED));

        $this->entityManager->expects(self::once())->method('flush');
        $handler->change($appointment, Appointment::STATUS_CONFIRMED);

        self::assertSame(Appointment::STATUS_CONFIRMED, $appointment->getStatus());
    }

    public function testAppointmentFormatterBuildsExpectedPayload(): void
    {
        $appointment = $this->createFutureAppointment();
        $formatter = new AppointmentFormatter(new AppointmentStatusWorkflow());

        $data = $formatter->format($appointment);

        self::assertSame($appointment->getId(), $data['id']);
        self::assertSame('Confirmé', $data['status']);
        self::assertSame(Appointment::STATUS_CONFIRMED, $data['statusCode']);
        self::assertTrue($data['isCancelable']);
        self::assertSame($appointment->getStartAt()->format(DATE_ATOM), $data['startAt']);
        self::assertSame($appointment->getEndAt()->format(DATE_ATOM), $data['endAt']);
        self::assertSame($appointment->getPrestation()->getName(), $data['prestation']['name']);
        self::assertSame($appointment->getPrestation()->getDurationMinutes(), $data['prestation']['durationMinutes']);
        self::assertSame($appointment->getPrestation()->getPriceCents(), $data['prestation']['priceCents']);
    }

    public function testAppointmentServiceBooksAvailableSlotAndPersistsAppointment(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $prestation = new Prestation('Diagnostic', 60, 9000);
        $startAt = new \DateTimeImmutable('2026-08-10T09:00:00+00:00');

        $workingDays = $this->createMock(WorkingDayConfigurationRepository::class);
        $workingDays->method('findAllOrdered')->willReturn([
            new WorkingDayConfiguration(
                0,
                true,
                new \DateTimeImmutable('09:00'),
                new \DateTimeImmutable('12:00'),
            ),
        ]);

        $appointments = $this->createMock(AppointmentRepository::class);
        $appointments->method('findBetween')->willReturn([]);

        $service = new AppointmentService(
            $appointments,
            $workingDays,
            new AvailabilityService($workingDays, $appointments),
            new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $this->persistence),
            $this->persistence,
            new DoctrineTransactionManager($this->entityManager),
        );

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Appointment $appointment) use ($user, $prestation, $startAt): bool {
                self::assertSame($user, $appointment->getUser());
                self::assertSame($prestation, $appointment->getPrestation());
                self::assertSame($startAt, $appointment->getStartAt());

                return true;
            }));
        $this->entityManager->expects(self::once())->method('flush');

        $appointment = $service->book($user, $prestation, $startAt);
        self::assertSame('2026-08-10T10:00:00+00:00', $appointment->getEndAt()->format(DATE_ATOM));
    }

    public function testAppointmentServiceRejectsUnavailableSlot(): void
    {
        $service = new AppointmentService(
            $this->createMock(AppointmentRepository::class),
            $this->createMock(WorkingDayConfigurationRepository::class),
            new AvailabilityService(
                $this->createMock(WorkingDayConfigurationRepository::class),
                $this->createMock(AppointmentRepository::class),
            ),
            new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $this->persistence),
            $this->persistence,
            new DoctrineTransactionManager($this->entityManager),
        );

        $this->expectException(InvalidAppointmentSlotException::class);
        $this->expectExceptionMessage('Ce creneau n\'est plus disponible.');
        $service->book(
            new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female'),
            new Prestation('Diagnostic', 60, 9000),
            new \DateTimeImmutable('2026-08-10T09:00:00+00:00'),
        );
    }

    public function testAppointmentServiceSplitsFutureAndPastAppointments(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $future = new Appointment($user, new Prestation('Future', 60, 9000), new \DateTimeImmutable('2026-08-10T09:00:00+00:00'));
        $past = new Appointment($user, new Prestation('Past', 60, 9000), new \DateTimeImmutable('2026-07-01T09:00:00+00:00'));

        $appointments = $this->createMock(AppointmentRepository::class);
        $appointments->expects(self::once())->method('findForUser')->with($user)->willReturn([$future, $past]);

        $service = new AppointmentService(
            $appointments,
            $this->createMock(WorkingDayConfigurationRepository::class),
            new AvailabilityService(
                $this->createMock(WorkingDayConfigurationRepository::class),
                $appointments,
            ),
            new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $this->persistence),
            $this->persistence,
            new DoctrineTransactionManager($this->entityManager),
        );

        $result = $service->getAppointmentsForUser($user);
        self::assertSame([$future], $result['upcoming']);
        self::assertSame([$past], $result['past']);
    }

    public function testAppointmentServiceCancelsAndDelegatesStatusChanges(): void
    {
        $service = new AppointmentService(
            $this->createMock(AppointmentRepository::class),
            $this->createMock(WorkingDayConfigurationRepository::class),
            new AvailabilityService(
                $this->createMock(WorkingDayConfigurationRepository::class),
                $this->createMock(AppointmentRepository::class),
            ),
            new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $this->persistence),
            $this->persistence,
            new DoctrineTransactionManager($this->entityManager),
        );
        $appointment = $this->createFutureAppointment();

        $this->entityManager->expects(self::exactly(2))->method('flush');

        $service->cancel($appointment);
        self::assertTrue($appointment->isCancelled());

        try {
            $service->cancel($appointment);
            self::fail('Expected already cancelled exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Ce rendez-vous est déjà annulé.', $exception->getMessage());
        }

        $service->changeStatus($appointment, Appointment::STATUS_CONFIRMED);
        self::assertSame(Appointment::STATUS_CONFIRMED, $appointment->getStatus());
    }

    public function testPrestationServiceCreatesUpdatesListsAndDeletesWithNormalizedData(): void
    {
        $repository = $this->createMock(PrestationRepository::class);
        $service = new PrestationService(
            $repository,
            new PrestationPersistence($this->entityManager),
            Validation::createValidator(),
        );
        $existing = new Prestation('Audit initial', 30, 5000);

        $repository->expects(self::once())->method('findAllOrderedByName')->willReturn([$existing]);
        self::assertSame([$existing], $service->list());

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (Prestation $prestation): bool {
                self::assertSame('Audit premium', $prestation->getName());
                self::assertSame(45, $prestation->getDurationMinutes());
                self::assertSame(15000, $prestation->getPriceCents());

                return true;
            }));
        $this->entityManager->expects(self::exactly(3))->method('flush');
        $this->entityManager->expects(self::once())->method('remove')->with($existing);

        $created = $service->create('Audit premium', 45, 15000);
        self::assertSame('Audit premium', $created->getName());
        self::assertSame(45, $created->getDurationMinutes());
        self::assertSame(15000, $created->getPriceCents());

        $updated = $service->update($existing, 'Audit entreprise', 60, 20000);
        self::assertSame($existing, $updated);
        self::assertSame('Audit entreprise', $existing->getName());
        self::assertSame(60, $existing->getDurationMinutes());
        self::assertSame(20000, $existing->getPriceCents());

        $service->delete($existing);
    }

    public function testPrestationServiceRejectsInvalidBusinessData(): void
    {
        $service = new PrestationService(
            $this->createMock(PrestationRepository::class),
            new PrestationPersistence($this->entityManager),
            Validation::createValidator(),
        );

        try {
            $service->create('', 45, 1000);
            self::fail('Blank name should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('La prestation doit avoir un nom.', $exception->getMessage());
        }

        try {
            $service->create('Audit', 0, 1000);
            self::fail('Zero duration should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('La duree doit etre superieure a 0.', $exception->getMessage());
        }

        try {
            $service->create('Audit', 60, -1);
            self::fail('Negative price should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('Le prix doit etre positif.', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La duree ne peut depasser 8 heures.');
        $service->create('Audit', 481, 1000);
    }

    public function testWorkingDayConfigurationServiceSeedsDefaultConfiguration(): void
    {
        $repository = $this->createMock(WorkingDayConfigurationRepository::class);
        $repository->expects(self::once())->method('findAllOrdered')->willReturn([]);

        $service = new WorkingDayConfigurationService(
            $repository,
            new WorkingDayConfigurationPersistence($this->entityManager),
        );

        $this->entityManager->expects(self::exactly(7))->method('persist')->with(self::isInstanceOf(WorkingDayConfiguration::class));
        $this->entityManager->expects(self::once())->method('flush');

        $configurations = $service->list();

        self::assertCount(7, $configurations);
        self::assertTrue($configurations[0]->isWorkingDay());
        self::assertSame('09:00', $configurations[0]->getStartTime()?->format('H:i'));
        self::assertSame('19:00', $configurations[0]->getEndTime()?->format('H:i'));
        self::assertSame([['start' => '12:00', 'end' => '13:00']], $configurations[0]->getBreaks());
        self::assertFalse($configurations[5]->isWorkingDay());
        self::assertNull($configurations[5]->getStartTime());
        self::assertSame([], $configurations[5]->getBreaks());
    }

    public function testWorkingDayConfigurationServiceListReturnsExistingConfigurationWithoutSeeding(): void
    {
        $existing = new WorkingDayConfiguration(
            2,
            true,
            new \DateTimeImmutable('10:00'),
            new \DateTimeImmutable('18:00'),
            [['start' => '13:00', 'end' => '14:00']],
        );
        $repository = $this->createMock(WorkingDayConfigurationRepository::class);
        $repository->expects(self::once())->method('findAllOrdered')->willReturn([$existing]);

        $service = new WorkingDayConfigurationService(
            $repository,
            new WorkingDayConfigurationPersistence($this->entityManager),
        );

        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('flush');

        $result = $service->list();

        self::assertSame([$existing], $result);
        self::assertSame('10:00', $result[0]->getStartTime()?->format('H:i'));
    }

    public function testWorkingDayConfigurationServiceUpdatesExistingAndNewDays(): void
    {
        $monday = new WorkingDayConfiguration(
            0,
            true,
            new \DateTimeImmutable('08:00'),
            new \DateTimeImmutable('18:00'),
            [['start' => '12:00', 'end' => '12:30']],
        );
        $repository = $this->createMock(WorkingDayConfigurationRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOneByDay')
            ->willReturnCallback(static fn (int $day): ?WorkingDayConfiguration => match ($day) {
                0 => $monday,
                1 => null,
                default => null,
            });

        $service = new WorkingDayConfigurationService(
            $repository,
            new WorkingDayConfigurationPersistence($this->entityManager),
        );

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (WorkingDayConfiguration $configuration): bool {
                self::assertSame(1, $configuration->getDayOfWeek());
                self::assertFalse($configuration->isWorkingDay());

                return true;
            }));
        $this->entityManager->expects(self::once())->method('flush');

        $result = $service->update([
            new WorkingDayData(0, true, '09:30', '17:30', [['start' => '13:00', 'end' => '14:00']]),
            new WorkingDayData(1, false, null, null),
        ]);

        self::assertCount(2, $result);
        self::assertSame('09:30', $monday->getStartTime()?->format('H:i'));
        self::assertSame('17:30', $monday->getEndTime()?->format('H:i'));
        self::assertSame([['start' => '13:00', 'end' => '14:00']], $monday->getBreaks());
        self::assertFalse($result[1]->isWorkingDay());
        self::assertNull($result[1]->getStartTime());
        self::assertNull($result[1]->getEndTime());
        self::assertSame([], $result[1]->getBreaks());
    }

    public function testWorkingDayConfigurationServiceRejectsInvalidTimes(): void
    {
        $repository = $this->createMock(WorkingDayConfigurationRepository::class);
        $repository->method('findOneByDay')->willReturn(null);
        $service = new WorkingDayConfigurationService(
            $repository,
            new WorkingDayConfigurationPersistence($this->entityManager),
        );

        try {
            $service->update([new WorkingDayData(0, true, 'invalid', '10:00')]);
            self::fail('Invalid time format should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(
                'Les heures de debut et fin doivent etre renseignees au format HH:MM.',
                $exception->getMessage(),
            );
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'heure de fin doit etre posterieure a l\'heure de debut.');
        $service->update([new WorkingDayData(0, true, '14:00', '14:00')]);
    }

    public function testWorkingDayConfigurationServiceRejectsMissingStartOrEndTimeForWorkingDay(): void
    {
        $repository = $this->createMock(WorkingDayConfigurationRepository::class);
        $repository->method('findOneByDay')->willReturn(null);
        $service = new WorkingDayConfigurationService(
            $repository,
            new WorkingDayConfigurationPersistence($this->entityManager),
        );

        try {
            $service->update([new WorkingDayData(0, true, null, '10:00')]);
            self::fail('Missing start time should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame(
                'Les heures de debut et fin doivent etre renseignees au format HH:MM.',
                $exception->getMessage(),
            );
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les heures de debut et fin doivent etre renseignees au format HH:MM.');
        $service->update([new WorkingDayData(0, true, '09:00', null)]);
    }

    private function createFutureAppointment(): Appointment
    {
        return $this->createAppointmentAt(new \DateTimeImmutable('2026-08-10T09:00:00+00:00'));
    }

    private function createAppointmentAt(\DateTimeImmutable $startAt): Appointment
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $prestation = new Prestation('Diagnostic', 45, 9000);

        return new Appointment($user, $prestation, $startAt);
    }
}
