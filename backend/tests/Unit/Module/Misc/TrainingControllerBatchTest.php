<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Training\UI\Controller\Admin\DeleteTrainingController;
use App\Module\Training\UI\Controller\Admin\DeleteTrainingSessionController;
use App\Module\Training\UI\Controller\Admin\ListTrainingEnrollmentsController;
use App\Module\Training\UI\Controller\Admin\ListTrainingSessionsController;
use App\Module\Training\UI\Controller\Admin\ListTrainingsController;
use App\Module\Training\UI\Controller\Admin\ShowTrainingController as AdminShowTrainingController;
use App\Module\Training\UI\Controller\Client\ListMyTrainingEnrollmentsController;
use App\Module\Training\UI\Controller\PublicApi\ShowTrainingController as PublicShowTrainingController;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Infrastructure\Repository\TrainingRepository;
use App\Module\Training\Infrastructure\Repository\TrainingSessionRepository;
use App\Module\Training\Application\Service\TrainingFormatter;
use App\Module\Training\Application\Service\TrainingMetadataFormatter;
use App\Module\Training\Application\Service\TrainingWriter;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Persistence\DoctrinePersistence;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrainingControllerBatchTest extends TestCase
{
    public function testTrainingShowListAndDeleteControllers(): void
    {
        $training = $this->training();
        $this->setId($training, 6);

        $trainings = $this->createMock(TrainingRepository::class);
        $trainings->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $training, null, $training);
        $trainings->expects(self::once())->method('findBy')->with([], ['title' => 'ASC'], 20, 20)->willReturn([$training]);
        $trainings->expects(self::once())->method('count')->with([])->willReturn(21);
        $trainings->expects(self::exactly(2))->method('findOneBy')->with(['slug' => 'seo', 'isActive' => true])->willReturnOnConsecutiveCalls($training, null);

        $enrollments = $this->createMock(TrainingEnrollmentRepository::class);
        $enrollments->method('countActiveForSession')->willReturn(0);
        $sessions = $this->createMock(TrainingSessionRepository::class);
        $sessions->expects(self::once())->method('findUpcomingForTraining')->with($training)->willReturn([$this->session($training)]);

        $formatter = $this->formatter($enrollments);

        $show = new AdminShowTrainingController($trainings, $formatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $show(404)->getStatusCode());
        $showPayload = json_decode((string) $show(6)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('SEO', $showPayload['data']['title']);

        $list = new ListTrainingsController($trainings, $formatter);
        $listPayload = json_decode((string) $list(new Request(['page' => '2']))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $listPayload['data']['meta']['page']);
        self::assertSame('seo', $listPayload['data']['items'][0]['slug']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($training);
        $entityManager->expects(self::once())->method('flush');
        $writer = new TrainingWriter(new DoctrinePersistence($entityManager));

        $delete = new DeleteTrainingController($trainings, $writer);
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete(6)->getStatusCode());

        $public = new PublicShowTrainingController($trainings, $sessions, $formatter);
        $publicPayload = json_decode((string) $public('seo')->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('seo', $publicPayload['data']['training']['slug']);
        self::assertSame('Distanciel', $publicPayload['data']['sessions'][0]['formatLabel']);
        self::assertSame(Response::HTTP_NOT_FOUND, $public('seo')->getStatusCode());
    }

    public function testTrainingSessionAndEnrollmentControllers(): void
    {
        $training = $this->training();
        $session = $this->session($training);
        $this->setId($session, 14);
        $enrollment = new TrainingEnrollment($session, $this->user(), 35000);
        $this->setId($enrollment, 16);
        $enrollment->setStatus(TrainingEnrollment::STATUS_PAID)->setPaidAt(new \DateTimeImmutable('2026-07-29T09:00:00+00:00'));

        $sessionRepo = $this->createMock(TrainingSessionRepository::class);
        $sessionRepo->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls(null, $session);
        $sessionRepo->expects(self::once())->method('findBy')->with([], ['startsAt' => 'DESC'], 20, 0)->willReturn([$session]);
        $sessionRepo->expects(self::once())->method('count')->with([])->willReturn(1);

        $enrollmentRepo = $this->createMock(TrainingEnrollmentRepository::class);
        $enrollmentRepo->expects(self::once())->method('findBy')->with([], ['createdAt' => 'DESC'], 20, 0)->willReturn([$enrollment]);
        $enrollmentRepo->expects(self::once())->method('count')->with([])->willReturn(1);
        $enrollmentRepo->expects(self::once())->method('findForUser')->with($enrollment->getUser())->willReturn([$enrollment]);
        $enrollmentRepo->method('countActiveForSession')->with($session)->willReturn(1);

        $formatter = $this->formatter($enrollmentRepo);

        $sessionsList = new ListTrainingSessionsController($sessionRepo, $formatter);
        $sessionsPayload = json_decode((string) $sessionsList(new Request())->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $sessionsPayload['data']['items'][0]['remainingSeats']);

        $enrollmentsList = new ListTrainingEnrollmentsController($enrollmentRepo, $formatter);
        $enrollmentsPayload = json_decode((string) $enrollmentsList(new Request())->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Payée', $enrollmentsPayload['data']['items'][0]['statusLabel']);

        $client = new class($enrollmentRepo, $formatter, $enrollment->getUser()) extends ListMyTrainingEnrollmentsController {
            public function __construct(TrainingEnrollmentRepository $enrollments, TrainingFormatter $formatter, private readonly User $user)
            {
                parent::__construct($enrollments, $formatter);
            }

            public function getUser(): ?User
            {
                return $this->user;
            }
        };
        $clientPayload = json_decode((string) $client()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(16, $clientPayload['data']['items'][0]['id']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($session);
        $entityManager->expects(self::once())->method('flush');
        $delete = new DeleteTrainingSessionController($sessionRepo, new TrainingWriter(new DoctrinePersistence($entityManager)));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete(14)->getStatusCode());
    }

    private function formatter(TrainingEnrollmentRepository $enrollments): TrainingFormatter
    {
        $categories = $this->createMock(TrainingCategoryRepository::class);
        $categories->method('findOrdered')->willReturn([]);

        return new TrainingFormatter($enrollments, new TrainingMetadataFormatter($categories));
    }

    private function training(): Training
    {
        $training = new Training('SEO', 'seo', 420, 35000);
        $training
            ->setShortDescription('Short')
            ->setObjective('Obj')
            ->setAudience('Audience')
            ->setCategory('marketing')
            ->setAvailableFormats(['remote']);
        $training->addRoadmapItem(new TrainingRoadmapItem(1, 'Analyse'));

        return $training;
    }

    private function session(Training $training): TrainingSession
    {
        return (new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable('2026-08-01T09:00:00+00:00'),
            new \DateTimeImmutable('2026-08-02T17:00:00+00:00'),
            2,
        ))
            ->setDailyStartTime(new \DateTimeImmutable('09:00'))
            ->setDailyEndTime(new \DateTimeImmutable('17:00'))
            ->setIncludeWeekends(false)
            ->setMeetingUrl('https://meet.example.test');
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
