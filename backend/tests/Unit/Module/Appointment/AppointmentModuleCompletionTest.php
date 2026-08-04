<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment;

use App\Module\Admin\UI\Appointment\Controller\CreatePrestationController as AdminCreatePrestationController;
use App\Module\Admin\UI\Appointment\Controller\DeletePrestationController as AdminDeletePrestationController;
use App\Module\Admin\UI\Appointment\Controller\GetConfigurationController as AdminGetConfigurationController;
use App\Module\Admin\UI\Appointment\Controller\ListAppointmentsController as AdminListAppointmentsController;
use App\Module\Admin\UI\Appointment\Controller\ListPrestationController as AdminListPrestationController;
use App\Module\Admin\UI\Appointment\Controller\ShowPrestationController as AdminShowPrestationController;
use App\Module\Admin\UI\Appointment\Controller\UpdateConfigurationController as AdminUpdateConfigurationController;
use App\Module\Admin\UI\Appointment\Controller\UpdatePrestationController as AdminUpdatePrestationController;
use App\Module\Appointment\UI\Controller\Client\CreateAppointmentController;
use App\Module\Appointment\UI\Controller\Client\ListMyAppointmentsController;
use App\Module\Appointment\UI\Controller\Client\UpdateAppointmentStatusController;
use App\Module\Appointment\UI\Controller\PublicApi\PublicAvailabilityController;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Domain\Security\AppointmentAccessPolicy;
use App\Module\Appointment\Application\Service\AppointmentFormatter;
use App\Module\Appointment\Application\Service\AppointmentService;
use App\Module\Appointment\Application\Service\AppointmentStatusWorkflow;
use App\Module\Appointment\Application\Service\ChangeAppointmentStatusHandler;
use App\Module\Appointment\Application\Service\AvailabilityService;
use App\Module\Appointment\Application\Service\PrestationPersistence;
use App\Module\Appointment\Application\Service\PrestationService;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationPersistence;
use App\Module\Appointment\Application\Service\WorkingDayConfigurationService;
use App\Module\Appointment\Application\Service\WorkingDayPayloadMapper;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Persistence\DoctrineTransactionManager;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AppointmentModuleCompletionTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testRepositoriesQueryWorkingDaysPrestationsAndAppointments(): void
    {
        [$user, $prestation] = $this->seedSchedule();
        $old = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-03T09:00:00+00:00'));
        $future = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-10T09:00:00+00:00'));
        $cancelled = (new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-10T10:00:00+00:00')))->cancel();
        foreach ([$old, $future, $cancelled] as $appointment) {
            $this->entityManager()->persist($appointment);
        }
        $this->entityManager()->flush();

        self::assertSame('Diagnostic', $this->prestations()->findAllOrderedByName()[0]->getName());
        self::assertSame(0, $this->workingDays()->findOneByDay(0)?->getDayOfWeek());
        $this->entityManager()->beginTransaction();
        try {
            self::assertSame(0, $this->workingDays()->findOneByDayForUpdate(0)?->getDayOfWeek());
        } finally {
            $this->entityManager()->commit();
        }
        self::assertCount(7, $this->workingDays()->findAllOrdered());
        self::assertSame([$future], $this->appointments()->findBetween(
            new \DateTimeImmutable('2026-08-10T08:00:00+00:00'),
            new \DateTimeImmutable('2026-08-10T11:00:00+00:00'),
        ));
        self::assertSame([$cancelled, $future, $old], $this->appointments()->findForUser($user));
        self::assertSame([$cancelled], $this->appointments()->findForUser($user, Appointment::STATUS_CANCELLED));

        $this->prestations()->remove($prestation, true);
        self::assertSame([], $this->prestations()->findAllOrderedByName());
    }

    public function testPublicAvailabilityControllerCoversValidationNotFoundAndSlots(): void
    {
        [, $prestation] = $this->seedSchedule();
        $controller = new PublicAvailabilityController($this->availability(), $this->prestations());

        self::assertSame(400, $controller(Request::create('/'))->getStatusCode());
        self::assertSame(400, $controller(Request::create('/?start=2026-08-10T11:00:00%2B00:00&end=2026-08-10T10:00:00%2B00:00&prestationId=1'))->getStatusCode());
        self::assertSame(404, $controller(Request::create('/?start=2026-08-10T08:00:00%2B00:00&end=2026-08-10T12:00:00%2B00:00&prestationId=999'))->getStatusCode());

        $response = $controller(Request::create(sprintf(
            '/?start=2026-08-10T08:00:00%%2B00:00&end=2026-08-10T12:00:00%%2B00:00&prestationId=%d',
            $prestation->getId(),
        )));
        $payload = $this->payload($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('2026-08-10T09:00:00+00:00', $payload['data']['slots'][0]['start']);
    }

    public function testClientControllersCreateListAndUpdateStatuses(): void
    {
        [$user, $prestation] = $this->seedSchedule();
        $service = $this->appointmentService();
        $formatter = $this->appointmentFormatter();

        $create = new CreateAppointmentController($service, $formatter, $this->prestations(), $this->validator());
        $create->setContainer($this->container($user));
        self::assertSame(400, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(404, $create($this->jsonRequest(['prestationId' => 999, 'startAt' => '2026-08-10T09:00:00+00:00']))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['prestationId' => $prestation->getId(), 'startAt' => 'not-a-date']))->getStatusCode());

        $created = $create($this->jsonRequest(['prestationId' => $prestation->getId(), 'startAt' => '2026-08-10T09:00:00+00:00']));
        self::assertSame(201, $created->getStatusCode());
        $appointmentId = (int) $this->payload($created)['data']['id'];

        $list = new ListMyAppointmentsController($service);
        $list->setContainer($this->container($user));
        $listPayload = $this->payload($list());
        self::assertSame($appointmentId, $listPayload['data']['upcoming'][0]['id']);

        $update = new UpdateAppointmentStatusController($this->appointments(), $service, $formatter, $this->validator(), new AppointmentAccessPolicy());
        $update->setContainer($this->container($user));
        self::assertSame(404, $update(999, $this->jsonRequest(['status' => Appointment::STATUS_CANCELLED], 'PATCH'))->getStatusCode());
        self::assertSame(400, $update($appointmentId, Request::create('/', 'PATCH', server: [], content: '{bad'))->getStatusCode());

        $cancelled = $update($appointmentId, $this->jsonRequest(['status' => Appointment::STATUS_CANCELLED], 'PATCH'));
        self::assertSame(200, $cancelled->getStatusCode());
        self::assertSame(Appointment::STATUS_CANCELLED, $this->appointments()->find($appointmentId)?->getStatus());

        $other = $this->persistUser('other-appointment@example.test');
        $update->setContainer($this->container($other, false));
        self::assertSame(403, $update($appointmentId, $this->jsonRequest(['status' => Appointment::STATUS_CONFIRMED], 'PATCH'))->getStatusCode());

        $update->setContainer($this->container($other, true));
        self::assertSame(200, $update($appointmentId, $this->jsonRequest(['status' => Appointment::STATUS_CONFIRMED], 'PATCH'))->getStatusCode());
    }

    public function testAdminAppointmentControllersCoverPrestationsConfigurationAndAppointments(): void
    {
        [$user, $prestation] = $this->seedSchedule();
        $appointment = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-10T09:00:00+00:00'));
        $this->entityManager()->persist($appointment);
        $this->entityManager()->flush();

        $prestationService = $this->prestationService();
        $create = new AdminCreatePrestationController($prestationService, $this->validator());
        self::assertSame(400, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Broken', 'durationMinutes' => 30, 'price' => 'bad']))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Too expensive', 'durationMinutes' => 30, 'price' => 1000001]))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest(['name' => 'Audit express', 'durationMinutes' => 30, 'price' => '49,90']))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest(['name' => 'Audit int', 'durationMinutes' => 45, 'price' => 50]))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest(['name' => 'Audit float', 'durationMinutes' => 45, 'price' => 50.5]))->getStatusCode());
        self::assertSame(500, (new AdminCreatePrestationController($this->failingPrestationService(), $this->validator()))($this->jsonRequest(['name' => 'Failure', 'durationMinutes' => 30, 'price' => 10]))->getStatusCode());

        $list = new AdminListPrestationController($prestationService);
        self::assertSame(200, $list()->getStatusCode());

        $show = new AdminShowPrestationController($this->prestations());
        self::assertSame(404, $show(999)->getStatusCode());
        self::assertSame(200, $show((int) $prestation->getId())->getStatusCode());

        $update = new AdminUpdatePrestationController($this->prestations(), $prestationService, $this->validator());
        self::assertSame(404, $update(999, $this->jsonRequest(['name' => 'Nope', 'durationMinutes' => 30, 'price' => 10], 'PUT'))->getStatusCode());
        self::assertSame(400, $update((int) $prestation->getId(), Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Broken', 'durationMinutes' => 30, 'price' => 'bad'], 'PUT'))->getStatusCode());
        self::assertSame(422, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Too expensive', 'durationMinutes' => 30, 'price' => 1000001], 'PUT'))->getStatusCode());
        self::assertSame(200, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Diagnostic updated', 'durationMinutes' => 75, 'price' => '120.20'], 'PUT'))->getStatusCode());
        self::assertSame(200, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Diagnostic float', 'durationMinutes' => 75, 'price' => 120.2], 'PUT'))->getStatusCode());
        self::assertSame(500, (new AdminUpdatePrestationController($this->prestations(), $this->failingPrestationService(), $this->validator()))((int) $prestation->getId(), $this->jsonRequest(['name' => 'Failure', 'durationMinutes' => 30, 'price' => 10], 'PUT'))->getStatusCode());

        $adminAppointments = new AdminListAppointmentsController($this->appointments());
        self::assertSame(200, $adminAppointments(Request::create('/?page=1&perPage=5'))->getStatusCode());

        $configurationService = new WorkingDayConfigurationService($this->workingDays(), new WorkingDayConfigurationPersistence($this->entityManager()));
        $getConfiguration = new AdminGetConfigurationController($configurationService);
        self::assertSame(200, $getConfiguration()->getStatusCode());

        $updateConfiguration = new AdminUpdateConfigurationController($configurationService, new WorkingDayPayloadMapper(), $this->validator());
        self::assertSame(400, $updateConfiguration(Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(400, $updateConfiguration($this->jsonRequest(['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true, 'startTime' => '14:00', 'endTime' => '13:00']]], 'PUT'))->getStatusCode());
        self::assertSame(200, $updateConfiguration($this->jsonRequest(['days' => [
            ['dayOfWeek' => 0, 'isWorkingDay' => true, 'startTime' => '08:30', 'endTime' => '17:30', 'breaks' => [['start' => '12:00', 'end' => '12:30']]],
            ['dayOfWeek' => 6, 'isWorkingDay' => false],
        ]], 'PUT'))->getStatusCode());

        $delete = new AdminDeletePrestationController($this->prestations(), new PrestationService($this->prestations(), new PrestationPersistence($this->entityManager()), Validation::createValidator()));
        self::assertSame(404, $delete(999)->getStatusCode());
        $createdId = (int) $this->payload($create($this->jsonRequest(['name' => 'Temporary', 'durationMinutes' => 15, 'price' => 5])))['data']['id'];
        self::assertSame(200, $delete($createdId)->getStatusCode());
    }

    /** @return array{User,Prestation} */
    private function seedSchedule(): array
    {
        $user = $this->persistUser('appointment-owner@example.test');
        $prestation = new Prestation('Diagnostic', 60, 9000);
        $this->entityManager()->persist($prestation);
        for ($day = 0; $day < 7; ++$day) {
            $this->entityManager()->persist(new WorkingDayConfiguration(
                $day,
                0 === $day,
                0 === $day ? new \DateTimeImmutable('09:00') : null,
                0 === $day ? new \DateTimeImmutable('12:00') : null,
                [],
            ));
        }
        $this->entityManager()->flush();

        return [$user, $prestation];
    }

    private function persistUser(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function appointmentService(): AppointmentService
    {
        $persistence = new DoctrineUnitOfWork($this->entityManager());

        return new AppointmentService(
            $this->appointments(),
            $this->workingDays(),
            $this->availability(),
            new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $persistence),
            $persistence,
            new DoctrineTransactionManager($this->entityManager()),
        );
    }

    private function prestationService(): PrestationService
    {
        return new PrestationService(
            $this->prestations(),
            new PrestationPersistence($this->entityManager()),
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    private function failingPrestationService(): PrestationService
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willThrowException(new \RuntimeException('storage down'));

        return new PrestationService(
            $this->prestations(),
            new PrestationPersistence($this->entityManager()),
            $validator,
        );
    }

    private function appointmentFormatter(): AppointmentFormatter
    {
        return new AppointmentFormatter(new AppointmentStatusWorkflow());
    }

    private function availability(): AvailabilityService
    {
        return new AvailabilityService($this->workingDays(), $this->appointments());
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(Prestation::class),
            $entityManager->getClassMetadata(WorkingDayConfiguration::class),
            $entityManager->getClassMetadata(Appointment::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    private function appointments(): AppointmentRepository
    {
        return new AppointmentRepository($this->registry());
    }

    private function prestations(): PrestationRepository
    {
        return new PrestationRepository($this->registry());
    }

    private function workingDays(): WorkingDayConfigurationRepository
    {
        return new WorkingDayConfigurationRepository($this->registry());
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function container(User $user, bool $isAdmin = false): Container
    {
        if ($isAdmin) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn($isAdmin);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);
        $container->set('security.authorization_checker', $authorizationChecker);

        return $container;
    }
}
