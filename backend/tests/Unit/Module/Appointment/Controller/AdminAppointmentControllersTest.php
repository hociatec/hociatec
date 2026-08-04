<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Controller;

use App\Module\Admin\UI\Appointment\Controller\CreatePrestationController;
use App\Module\Admin\UI\Appointment\Controller\ListAppointmentsController;
use App\Module\Admin\UI\Appointment\Controller\UpdateConfigurationController;
use App\Module\Admin\UI\Appointment\Controller\UpdatePrestationController;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Application\Service\PrestationPersistence;
use App\Module\Appointment\Application\Service\PrestationService;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationPersistence;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationService;
use App\Module\Appointment\Application\Service\WorkingDayPayloadMapper;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

final class AdminAppointmentControllersTest extends TestCase
{
    public function testCreatePrestationControllerCoversValidationBusinessAndSuccessPaths(): void
    {
        $controller = new CreatePrestationController($this->prestationService(), $this->validator());

        $invalidJson = $controller->__invoke(Request::create('/', 'POST', server: [], content: '{bad'));
        self::assertSame(400, $invalidJson->getStatusCode());
        self::assertSame('Payload JSON invalide.', $this->payload($invalidJson)['message']);

        $negativePrice = $controller->__invoke($this->jsonRequest(['name' => 'Audit', 'durationMinutes' => 45, 'price' => 'bad']));
        self::assertSame(422, $negativePrice->getStatusCode());
        self::assertSame('Le prix doit etre positif.', $this->payload($negativePrice)['message']);

        $businessError = $controller->__invoke($this->jsonRequest([
            'name' => str_repeat('A', 121),
            'durationMinutes' => 45,
            'price' => '12,50',
        ]));
        self::assertSame(422, $businessError->getStatusCode());
        self::assertStringContainsString('Le nom ne doit pas depasser 120 caracteres.', $this->payload($businessError)['message']);

        $runtimeController = new CreatePrestationController($this->prestationService(throwOnPersist: true), $this->validator());
        $internalError = $runtimeController->__invoke($this->jsonRequest([
            'name' => 'Audit',
            'durationMinutes' => 45,
            'price' => 30,
        ]));
        self::assertSame(500, $internalError->getStatusCode());
        self::assertSame('Une erreur interne est survenue.', $this->payload($internalError)['message']);

        $success = $controller->__invoke($this->jsonRequest([
            'name' => 'Audit réussi',
            'durationMinutes' => 60,
            'price' => 15.0,
        ]));
        self::assertSame(201, $success->getStatusCode());
        self::assertSame('La prestation a bien été créée.', $this->payload($success)['message']);
        self::assertSame([
            'id' => null,
            'name' => 'Audit réussi',
            'durationMinutes' => 60,
            'priceCents' => 1500,
        ], $this->payload($success)['data']);
    }

    public function testUpdatePrestationControllerCoversNotFoundValidationAndSuccessPaths(): void
    {
        $repository = $this->createMock(PrestationRepository::class);
        $controller = new UpdatePrestationController($repository, $this->prestationService(), $this->validator());

        $repository->expects(self::exactly(6))
            ->method('find')
            ->with(9)
            ->willReturnCallback(function (): ?Prestation {
                static $call = 0;
                ++$call;

                return 1 === $call ? null : new Prestation('Initiale', 30, 5000);
            });

        $notFound = $controller->__invoke(9, $this->jsonRequest([]));
        self::assertSame(404, $notFound->getStatusCode());
        self::assertSame('Prestation introuvable.', $this->payload($notFound)['message']);

        $invalidJson = $controller->__invoke(9, Request::create('/', 'PUT', server: [], content: '{bad'));
        self::assertSame(400, $invalidJson->getStatusCode());
        self::assertSame('Payload JSON invalide.', $this->payload($invalidJson)['message']);

        $negativePrice = $controller->__invoke(9, $this->jsonRequest([
            'name' => 'Audit',
            'durationMinutes' => 45,
            'price' => 'bad',
        ], 'PUT'));
        self::assertSame(422, $negativePrice->getStatusCode());
        self::assertSame('Le prix doit etre positif.', $this->payload($negativePrice)['message']);

        $businessError = $controller->__invoke(9, $this->jsonRequest([
            'name' => str_repeat('B', 121),
            'durationMinutes' => 45,
            'price' => '25,50',
        ], 'PUT'));
        self::assertSame(422, $businessError->getStatusCode());
        self::assertStringContainsString('Le nom ne doit pas depasser 120 caracteres.', $this->payload($businessError)['message']);

        $runtimeController = new UpdatePrestationController($repository, $this->prestationService(throwOnFlush: true), $this->validator());
        $runtimeError = $runtimeController->__invoke(9, $this->jsonRequest([
            'name' => 'Audit runtime',
            'durationMinutes' => 45,
            'price' => 18,
        ], 'PUT'));
        self::assertSame(500, $runtimeError->getStatusCode());
        self::assertSame('Une erreur interne est survenue.', $this->payload($runtimeError)['message']);

        $success = $controller->__invoke(9, $this->jsonRequest([
            'name' => 'Audit premium',
            'durationMinutes' => 75,
            'price' => '19.99',
        ], 'PUT'));
        self::assertSame(200, $success->getStatusCode());
        self::assertSame('La prestation a bien été mise à jour.', $this->payload($success)['message']);
        self::assertSame([
            'id' => null,
            'name' => 'Audit premium',
            'durationMinutes' => 75,
            'priceCents' => 1999,
        ], $this->payload($success)['data']);
    }

    public function testListAppointmentsControllerBuildsPaginatedPayload(): void
    {
        $repository = $this->createMock(AppointmentRepository::class);
        $controller = new ListAppointmentsController($repository);
        $appointment = $this->appointment();

        $repository->expects(self::once())
            ->method('findBy')
            ->with([], ['startAt' => 'DESC'], 5, 5)
            ->willReturn([$appointment]);
        $repository->expects(self::once())->method('count')->with([])->willReturn(12);

        $response = $controller->__invoke(Request::create('/?page=2&perPage=5', 'GET'));
        $payload = $this->payload($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(12, $payload['data']['meta']['total']);
        self::assertSame(3, $payload['data']['meta']['totalPages']);
        self::assertSame('Confirmé', $payload['data']['items'][0]['status']);
        self::assertSame('ada@example.com', $payload['data']['items'][0]['user']['email']);
        self::assertSame('Diagnostic', $payload['data']['items'][0]['prestation']['name']);
    }

    public function testUpdateConfigurationControllerCoversPayloadErrorServiceErrorAndSuccess(): void
    {
        $mapper = new WorkingDayPayloadMapper();
        $controller = new UpdateConfigurationController($this->workingDayService(), $mapper, $this->validator());

        $invalidJson = $controller->__invoke(Request::create('/', 'PUT', server: [], content: '{bad'));
        self::assertSame(400, $invalidJson->getStatusCode());
        self::assertSame('Payload JSON invalide.', $this->payload($invalidJson)['message']);

        $invalidPayload = $controller->__invoke($this->jsonRequest(['days' => [['dayOfWeek' => 0, 'isWorkingDay' => 'yes']]], 'PUT'));
        self::assertSame(400, $invalidPayload->getStatusCode());
        self::assertSame('isWorkingDay doit être un booléen.', $this->payload($invalidPayload)['message']);

        $runtimeController = new UpdateConfigurationController($this->workingDayService(throwOnFlush: true), $mapper, $this->validator());
        $serviceError = $runtimeController->__invoke($this->jsonRequest([
            'days' => [[
                'dayOfWeek' => 0,
                'isWorkingDay' => true,
                'startTime' => '09:00',
                'endTime' => '17:00',
                'breaks' => [['start' => '12:00', 'end' => '13:00']],
            ]],
        ], 'PUT'));
        self::assertSame(400, $serviceError->getStatusCode());
        self::assertSame('Impossible de mettre a jour la configuration.', $this->payload($serviceError)['message']);

        $success = $controller->__invoke($this->jsonRequest([
            'days' => [[
                'dayOfWeek' => 0,
                'isWorkingDay' => true,
                'startTime' => '09:00',
                'endTime' => '17:00',
                'breaks' => [['start' => '12:00', 'end' => '13:00']],
            ]],
        ], 'PUT'));
        self::assertSame(200, $success->getStatusCode());
        self::assertSame('Lundi', $this->payload($success)['data']['days'][0]['dayLabel']);
        self::assertSame('09:00', $this->payload($success)['data']['days'][0]['startTime']);
        self::assertSame([['start' => '12:00', 'end' => '13:00']], $this->payload($success)['data']['days'][0]['breaks']);
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            new ConstraintViolationFormatter(),
        );
    }

    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function payload(object $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function appointment(): Appointment
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $prestation = new Prestation('Diagnostic', 45, 9000);
        $appointment = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-10T09:00:00+00:00'));

        $this->setId($user, 7);
        $this->setId($prestation, 5);
        $this->setId($appointment, 11);

        return $appointment;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private function prestationService(bool $throwOnPersist = false, bool $throwOnFlush = false): PrestationService
    {
        $repository = $this->createMock(PrestationRepository::class);
        $entityManager = $this->entityManager($throwOnPersist, $throwOnFlush);

        return new PrestationService(
            $repository,
            new PrestationPersistence($entityManager),
            Validation::createValidator(),
        );
    }

    private function workingDayService(bool $throwOnFlush = false): WorkingDayConfigurationService
    {
        $repository = $this->createMock(WorkingDayConfigurationRepository::class);
        $repository->method('findOneByDay')->willReturn(null);
        $entityManager = $this->entityManager(false, $throwOnFlush);

        return new WorkingDayConfigurationService(
            $repository,
            new WorkingDayConfigurationPersistence($entityManager),
        );
    }

    private function entityManager(bool $throwOnPersist = false, bool $throwOnFlush = false): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        if ($throwOnPersist) {
            $entityManager->method('persist')->willThrowException(new \RuntimeException('persist'));
        }
        if ($throwOnFlush) {
            $entityManager->method('flush')->willThrowException(new \RuntimeException('flush'));
        }

        return $entityManager;
    }
}
