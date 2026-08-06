<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training;

use App\Module\Order\Application\Security\CheckoutRedirectUrlValidator;
use App\Module\Order\Application\Workflow\StripeApiClient;
use App\Module\Training\Application\Exception\TrainingSessionUnavailableException;
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
use App\Module\Training\UI\Controller\Admin\SaveTrainingCategoryController;
use App\Module\Training\UI\Controller\Admin\SaveTrainingController;
use App\Module\Training\UI\Controller\Admin\SaveTrainingSessionController;
use App\Module\Training\UI\Controller\Admin\UpdateTrainingEnrollmentStatusController;
use App\Module\Training\UI\Controller\Client\CreateTrainingEnrollmentController;
use App\Module\Training\UI\Controller\PublicApi\ListTrainingsController;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Exception\ExternalServiceException;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TrainingModuleCompletionTest extends TestCase
{
    public function testTrainingRepositoriesQueryActiveSessionsAndEnrollments(): void
    {
        $em = $this->entityManager();
        [$training, $session, $user] = $this->persistTrainingGraph($em);
        $inactive = (new Training('Inactive', 'inactive', 60, 1000))->setIsActive(false)->setCategory('infra');
        $em->persist($inactive);
        $em->flush();

        $enrollment = (new TrainingEnrollment($session, $user, 1000))
            ->setStatus(TrainingEnrollment::STATUS_CONFIRMED)
            ->setStripeSessionId('cs_test')
            ->setScheduledStartsAt(new \DateTimeImmutable('2026-08-10T09:00:00+00:00'))
            ->setScheduledEndsAt(new \DateTimeImmutable('2026-08-10T10:00:00+00:00'));
        $em->persist($enrollment);
        $em->flush();

        $trainings = $this->trainingRepository($em);
        self::assertSame(['SEO'], array_map(static fn (Training $item): string => $item->getTitle(), $trainings->findActive('web')));
        self::assertSame(1, $trainings->countActive('web'));
        self::assertSame(['SEO'], array_map(static fn (Training $item): string => $item->getTitle(), $trainings->findActivePaginated(null, 500, -10)));

        $sessions = $this->sessionRepository($em);
        self::assertSame([$session], $sessions->findUpcomingForTraining($training));
        $em->beginTransaction();
        self::assertSame($session, $sessions->findForUpdate((int) $session->getId()));
        self::assertNull($sessions->findForUpdate(999));
        $em->commit();

        $enrollments = $this->enrollmentRepository($em);
        self::assertSame(1, $enrollments->countActiveForSession($session));
        self::assertSame(1, $enrollments->countActiveForSessionSlot(
            $session,
            new \DateTimeImmutable('2026-08-10T09:30:00+00:00'),
            new \DateTimeImmutable('2026-08-10T10:30:00+00:00'),
        ));
        self::assertSame($enrollment, $enrollments->findOneForUserAndSession($user, $session));
        self::assertSame($enrollment, $enrollments->findOneByStripeSessionId('cs_test'));
        self::assertSame([$enrollment], $enrollments->findForUser($user));
        self::assertInstanceOf(TrainingRoadmapItemRepository::class, new TrainingRoadmapItemRepository($this->registry($em)));
    }

    public function testTrainingSaveControllersCoverCreateUpdateAndValidationBranches(): void
    {
        $em = $this->entityManager();
        [$training] = $this->persistTrainingGraph($em);
        $categories = $this->categoryRepository($em);
        $trainings = $this->trainingRepository($em);
        $sessions = $this->sessionRepository($em);
        $enrollments = $this->enrollmentRepository($em);
        $writer = new TrainingWriter(new DoctrineUnitOfWork($em));
        $formatter = new TrainingFormatter($enrollments, new TrainingMetadataFormatter($categories));
        $validator = $this->validator(8);

        $categoryController = new SaveTrainingCategoryController($categories, $writer, new TrainingCategoryFormatter());
        self::assertSame(Response::HTTP_BAD_REQUEST, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":""}'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":"Missing"}'), 999)->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":"Duplicate","slug":"web"}'))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":"Cloud","slug":"cloud","position":2,"isActive":false}'))->getStatusCode());

        $trainingController = new SaveTrainingController($trainings, $writer, $formatter, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $trainingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->trainingPayload(), JSON_THROW_ON_ERROR)), 999)->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $trainingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->trainingPayload(['title' => 'Cloud', 'slug' => 'cloud-training']), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $trainingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->trainingPayload(['title' => 'SEO Updated']), JSON_THROW_ON_ERROR)), $training->getId())->getStatusCode());

        $sessionController = new SaveTrainingSessionController($trainings, $sessions, $formatter, $writer, $validator);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training, [
            'endsAt' => '2026-08-10T08:00:00+00:00',
        ]), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training, [
            'dailyStartTime' => '18:00',
            'dailyEndTime' => '08:00',
        ]), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training, ['trainingId' => 999]), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training), JSON_THROW_ON_ERROR)), 999)->getStatusCode());
    }

    public function testTrainingListStatusAndEnrollmentControllers(): void
    {
        $em = $this->entityManager();
        [$training, $session, $user] = $this->persistTrainingGraph($em);
        $categories = $this->categoryRepository($em);
        $enrollments = $this->enrollmentRepository($em);
        $formatter = new TrainingFormatter($enrollments, new TrainingMetadataFormatter($categories));
        $writer = new TrainingWriter(new DoctrineUnitOfWork($em));

        $list = new ListTrainingsController($this->trainingRepository($em), $formatter);
        $listPayload = json_decode((string) $list(Request::create('/?category=web&page=1&perPage=5'))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('SEO', $listPayload['data']['items'][0]['title']);

        $enrollment = new TrainingEnrollment($session, $user, 0);
        $em->persist($enrollment);
        $em->flush();
        $status = new UpdateTrainingEnrollmentStatusController($enrollments, $formatter, $writer);
        self::assertSame(Response::HTTP_NOT_FOUND, $status(999, Request::create('/', 'PATCH', [], [], [], [], '{"status":"paid"}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $status((int) $enrollment->getId(), Request::create('/', 'PATCH', [], [], [], [], '{"status":"bogus"}'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $status((int) $enrollment->getId(), Request::create('/', 'PATCH', [], [], [], [], '{"status":"paid"}'))->getStatusCode());
        self::assertNotNull($enrollment->getPaidAt());

        $checkoutUser = $this->user('checkout@example.com');
        $em->persist($checkoutUser);
        $em->flush();
        $checkout = $this->checkoutService($em);
        $controller = new CreateTrainingEnrollmentController($checkout, $formatter);
        $controller->setContainer($this->controllerContainer($checkoutUser));
        $request = Request::create('/', 'POST', [], [], [], [], json_encode([
            'sessionId' => $session->getId(),
            'startsAt' => '2026-08-10T09:00:00+00:00',
        ], JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_CREATED, $controller($request)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $controller($request)->getStatusCode());

        $errorEm = $this->entityManager();
        [, $errorSession, $errorUser] = $this->persistTrainingGraph($errorEm);
        $errorController = new CreateTrainingEnrollmentController($this->checkoutService($errorEm), new TrainingFormatter($this->enrollmentRepository($errorEm), new TrainingMetadataFormatter($this->categoryRepository($errorEm))));
        $errorController->setContainer($this->controllerContainer($errorUser));
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(Request::create('/', 'POST', [], [], [], [], json_encode([
            'sessionId' => $session->getId(),
            'startsAt' => '',
        ], JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $errorController(Request::create('/', 'POST', [], [], [], [], '{"sessionId":0,"startsAt":"2026-08-10T09:00:00+00:00"}'))->getStatusCode());

        $anonymousController = new CreateTrainingEnrollmentController($this->checkoutService($this->entityManager()), $formatter);
        $anonymousController->setContainer($this->controllerContainer(null));
        self::assertSame(Response::HTTP_BAD_REQUEST, $anonymousController(Request::create('/', 'POST', [], [], [], [], '{"sessionId":1,"startsAt":"2026-08-10T09:00:00+00:00"}'))->getStatusCode());
    }

    public function testTrainingEnrollmentCheckoutServiceCoversMainBranches(): void
    {
        $em = $this->entityManager();
        [$training, $session, $user] = $this->persistTrainingGraph($em);
        $service = $this->checkoutService($em);

        $created = $service->enroll($user, (int) $session->getId(), '2026-08-10T09:00:00+00:00');
        self::assertTrue($created->created);
        self::assertSame(TrainingEnrollment::STATUS_CONFIRMED, $created->enrollment->getStatus());

        $created->enrollment->setStatus(TrainingEnrollment::STATUS_CONFIRMED);
        $em->flush();
        $existing = $service->enroll($user, (int) $session->getId(), '2026-08-10T09:00:00+00:00');
        self::assertFalse($existing->created);

        $session->setCapacity(1);
        $created->enrollment->setStatus(TrainingEnrollment::STATUS_CANCELLED);
        $busy = $this->user('busy@example.com');
        $em->persist($busy);
        $blocking = (new TrainingEnrollment($session, $busy, 0))
            ->setStatus(TrainingEnrollment::STATUS_CONFIRMED)
            ->setScheduledStartsAt(new \DateTimeImmutable('2026-08-10T09:00:00+00:00'))
            ->setScheduledEndsAt(new \DateTimeImmutable('2026-08-10T10:00:00+00:00'));
        $em->persist($blocking);
        $em->flush();
        try {
            $service->enroll($user, (int) $session->getId(), '2026-08-10T09:00:00+00:00');
            self::fail('Expected full session exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Cette session est complète.', $exception->getMessage());
        }

        $failureEm = $this->entityManager();
        [, $failureSession, $failureUser] = $this->persistTrainingGraph($failureEm);
        $failureService = $this->checkoutService($failureEm);

        try {
            $failureService->enroll($failureUser, (int) $failureSession->getId(), 'not a date');
            self::fail('Expected invalid start exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Créneau invalide.', $exception->getMessage());
        }

        $failureEm = $this->entityManager();
        [, $failureSession, $failureUser] = $this->persistTrainingGraph($failureEm);
        $failureService = $this->checkoutService($failureEm);

        try {
            $failureService->enroll($failureUser, (int) $failureSession->getId(), '');
            self::fail('Expected blank start exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Choisissez une date et une heure de début.', $exception->getMessage());
        }

        $failureEm = $this->entityManager();
        [, , $failureUser] = $this->persistTrainingGraph($failureEm);
        try {
            $this->checkoutService($failureEm)->enroll($failureUser, 0, '2026-08-10T09:00:00+00:00');
            self::fail('Expected missing session exception.');
        } catch (TrainingSessionUnavailableException) {
            self::assertTrue(true);
        }

        $paidEm = $this->entityManager();
        [, , $paidUser] = $this->persistTrainingGraph($paidEm);
        $paidService = $this->checkoutService($paidEm);
        $paidTraining = (new Training('Paid', 'paid', 60, 5000))->setIsActive(true);
        $paidSession = new TrainingSession($paidTraining, 'remote', new \DateTimeImmutable('2026-08-11T08:00:00+00:00'), new \DateTimeImmutable('2026-08-11T18:00:00+00:00'), 2);
        $paidEm->persist($paidTraining);
        $paidEm->persist($paidSession);
        $paidEm->flush();

        try {
            $paidService->enroll($paidUser, (int) $paidSession->getId(), '2026-08-11T09:00:00+00:00');
            self::fail('Expected unavailable Stripe exception.');
        } catch (ExternalServiceException) {
            self::assertTrue(true);
        }
    }

    private function checkoutService(EntityManager $em): TrainingEnrollmentCheckoutService
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
    private function persistTrainingGraph(EntityManager $em): array
    {
        $category = new TrainingCategory('Web', 'web');
        $training = (new Training('SEO', 'seo', 60, 0))->setCategory('web')->setIsActive(true);
        $training->addRoadmapItem(new TrainingRoadmapItem(1, 'Intro'));
        $session = new TrainingSession($training, 'onsite', new \DateTimeImmutable('2026-08-10T08:00:00+00:00'), new \DateTimeImmutable('2026-08-10T18:00:00+00:00'), 5);
        $user = $this->user();
        $em->persist($category);
        $em->persist($training);
        $em->persist($session);
        $em->persist($user);
        $em->flush();

        return [$training, $session, $user];
    }

    private function user(string $email = 'ada@example.com'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    /** @param array<string,mixed> $override */
    private function trainingPayload(array $override = []): array
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
    private function sessionPayload(Training $training, array $override = []): array
    {
        return $override + [
            'trainingId' => $training->getId(),
            'startsAt' => '2026-08-12T08:00:00+00:00',
            'endsAt' => '2026-08-12T18:00:00+00:00',
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

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    private function controllerContainer(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    private function entityManager(): EntityManager
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

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function trainingRepository(EntityManager $em): TrainingRepository
    {
        return new TrainingRepository($this->registry($em));
    }

    private function categoryRepository(EntityManager $em): TrainingCategoryRepository
    {
        return new TrainingCategoryRepository($this->registry($em));
    }

    private function sessionRepository(EntityManager $em): TrainingSessionRepository
    {
        return new TrainingSessionRepository($this->registry($em));
    }

    private function enrollmentRepository(EntityManager $em): TrainingEnrollmentRepository
    {
        return new TrainingEnrollmentRepository($this->registry($em));
    }
}
