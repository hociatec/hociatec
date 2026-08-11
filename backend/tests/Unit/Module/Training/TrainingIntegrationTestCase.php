<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training;

use App\Module\Order\Application\Security\CheckoutRedirectUrlValidator;
use App\Module\Order\Application\Workflow\StripeApiClient;
use App\Module\Training\Application\Calculator\TrainingAvailabilityCalculator;
use App\Module\Training\Application\Mapper\TrainingSlotValidator;
use App\Module\Training\Application\Projection\TrainingCategoryFormatter;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Projection\TrainingMetadataFormatter;
use App\Module\Training\Application\Workflow\TrainingEnrollmentCheckoutService;
use App\Module\Training\Application\Writer\TrainingWriter;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Infrastructure\Repository\TrainingRepository;
use App\Module\Training\Infrastructure\Repository\TrainingRoadmapItemRepository;
use App\Module\Training\Infrastructure\Repository\TrainingSessionRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class TrainingIntegrationTestCase extends TestCase
{
    protected const SESSION_START = '2026-08-12T08:00:00+00:00';
    protected const SESSION_END = '2026-08-12T18:00:00+00:00';
    protected const ENROLLMENT_START = '2026-08-12T09:00:00+00:00';
    protected const ENROLLMENT_END = '2026-08-12T10:00:00+00:00';
    protected const PAID_SESSION_START = '2026-08-13T08:00:00+00:00';
    protected const PAID_SESSION_END = '2026-08-13T18:00:00+00:00';
    protected const PAID_ENROLLMENT_START = '2026-08-13T09:00:00+00:00';

    protected function checkoutService(EntityManager $em): TrainingEnrollmentCheckoutService
    {
        return new TrainingEnrollmentCheckoutService(
            new \App\Module\Training\Application\Workflow\TrainingEnrollmentPorts(
                $this->sessionRepository($em),
                $this->enrollmentRepository($em),
                new TrainingSlotValidator(),
            ),
            new StripeApiClient(''),
            new DoctrineUnitOfWork($em),
            new DoctrineTransactionManager($em),
            new CheckoutRedirectUrlValidator('https://front.example.test/'),
            'https://front.example.test/',
        );
    }

    /**
     * @return array{Training, TrainingSession, User}
     */
    protected function persistTrainingGraph(EntityManager $em): array
    {
        $category = new TrainingCategory('Web', 'web');
        $training = (new Training('SEO', 'seo', 60, 0))->setCategory('web')->setIsActive(true);
        $training->addRoadmapItem(new TrainingRoadmapItem(1, 'Intro'));
        $session = new TrainingSession($training, 'onsite', new \DateTimeImmutable(self::SESSION_START), new \DateTimeImmutable(self::SESSION_END), 5);
        $user = $this->user();
        $em->persist($category);
        $em->persist($training);
        $em->persist($session);
        $em->persist($user);
        $em->flush();

        return [$training, $session, $user];
    }

    protected function user(string $email = 'ada@example.com'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    /** @param array<string,mixed> $override */
    protected function trainingPayload(array $override = []): array
    {
        return $override + [
            'title' => 'SEO',
            'slug' => 'seo',
            'durationMinutes' => 60,
            'priceCents' => 0,
            'category' => 'web',
            'availableFormats' => ['onsite'],
            'roadmap' => ['Intro'],
            'isActive' => true,
        ];
    }

    /** @param array<string,mixed> $override */
    protected function sessionPayload(Training $training, array $override = []): array
    {
        return $override + [
            'trainingId' => $training->getId(),
            'startsAt' => '2026-08-14T08:00:00+00:00',
            'endsAt' => '2026-08-14T18:00:00+00:00',
            'dailyStartTime' => '08:00',
            'dailyEndTime' => '18:00',
            'includeWeekends' => true,
            'format' => 'remote',
            'capacity' => 3,
            'location' => '',
            'meetingUrl' => 'https://meet.example.test',
            'status' => 'scheduled',
        ];
    }

    protected function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    protected function controllerContainer(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    protected function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Training::class),
            $em->getClassMetadata(TrainingCategory::class),
            $em->getClassMetadata(TrainingRoadmapItem::class),
            $em->getClassMetadata(TrainingSession::class),
            $em->getClassMetadata(TrainingEnrollment::class),
        ]);

        return $em;
    }

    protected function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    protected function trainingRepository(EntityManager $em): TrainingRepository
    {
        return new TrainingRepository($this->registry($em));
    }

    protected function categoryRepository(EntityManager $em): TrainingCategoryRepository
    {
        return new TrainingCategoryRepository($this->registry($em));
    }

    protected function sessionRepository(EntityManager $em): TrainingSessionRepository
    {
        return new TrainingSessionRepository($this->registry($em));
    }

    protected function enrollmentRepository(EntityManager $em): TrainingEnrollmentRepository
    {
        return new TrainingEnrollmentRepository($this->registry($em));
    }

    protected function writer(EntityManager $em): TrainingWriter
    {
        return new TrainingWriter(new DoctrineUnitOfWork($em));
    }

    protected function formatter(EntityManager $em): TrainingFormatter
    {
        return new TrainingFormatter($this->enrollmentRepository($em), new TrainingMetadataFormatter($this->categoryRepository($em)), new TrainingAvailabilityCalculator());
    }

    protected function categoryFormatter(): TrainingCategoryFormatter
    {
        return new TrainingCategoryFormatter();
    }

    protected function roadmapRepository(EntityManager $em): TrainingRoadmapItemRepository
    {
        return new TrainingRoadmapItemRepository($this->registry($em));
    }
}
