<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment;

use App\Module\Appointment\Application\Handler\ChangeAppointmentStatusHandler;
use App\Module\Appointment\Application\Mapper\WorkingDayPayloadMapper;
use App\Module\Appointment\Application\Projection\AppointmentFormatter;
use App\Module\Appointment\Application\Workflow\AppointmentService;
use App\Module\Appointment\Application\Workflow\AppointmentStatusWorkflow;
use App\Module\Appointment\Application\Workflow\AvailabilityService;
use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Module\Appointment\Application\Workflow\WorkingDayConfigurationService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Persistence\PrestationPersistence;
use App\Module\Appointment\Infrastructure\Persistence\WorkingDayConfigurationPersistence;
use App\Module\Appointment\Infrastructure\Repository\AppointmentRepository;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
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

abstract class AppointmentIntegrationTestCase extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    /** @return array{User,Prestation} */
    protected function seedSchedule(): array
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

    protected function persistUser(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    protected function appointmentService(): AppointmentService
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

    protected function prestationService(): PrestationService
    {
        return new PrestationService(
            $this->prestations(),
            new PrestationPersistence($this->entityManager()),
        );
    }

    protected function failingPrestationService(): PrestationService
    {
        return new PrestationService(
            $this->prestations(),
            new class implements \App\Module\Appointment\Application\Port\PrestationPersistencePort {
                public function save(object $entity): void
                {
                }

                public function delete(object $entity): void
                {
                }

                public function flush(): void
                {
                    throw new \RuntimeException('storage down');
                }
            },
        );
    }

    protected function appointmentFormatter(): AppointmentFormatter
    {
        return new AppointmentFormatter(new AppointmentStatusWorkflow());
    }

    protected function availability(): AvailabilityService
    {
        return new AvailabilityService($this->workingDays(), $this->appointments());
    }

    protected function entityManager(): EntityManager
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

    protected function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    protected function appointments(): AppointmentRepository
    {
        return new AppointmentRepository($this->registry());
    }

    protected function prestations(): PrestationRepository
    {
        return new PrestationRepository($this->registry());
    }

    protected function workingDays(): WorkingDayConfigurationRepository
    {
        return new WorkingDayConfigurationRepository($this->registry());
    }

    protected function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    protected function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    protected function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    protected function container(User $user, bool $isAdmin = false): Container
    {
        if ($isAdmin) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn($isAdmin);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);
        $container->set('security.authorization_checker', $authorizationChecker);

        return $container;
    }
}
