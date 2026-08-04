<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Service;

use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\Infrastructure\Repository\TrainingEnrollmentRepository;
use App\Module\Training\Application\Projection\TrainingFormatter;
use App\Module\Training\Application\Projection\TrainingMetadataFormatter;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class TrainingFormatterTest extends TestCase
{
    public function testFormatTrainingFiltersBlankFormatsAndMapsRoadmapAndCategory(): void
    {
        $training = new Training('Formation sécurité', 'formation-securite', 420, 90000);
        $this->setId($training, 15);
        $training
            ->setShortDescription('Initiation')
            ->setObjective('Protéger son SI')
            ->setAudience('RSSI')
            ->setCategory('cyber')
            ->setAvailableFormats(['remote', ' ', 'onsite'])
            ->setIsActive(false);

        $first = (new TrainingRoadmapItem(1, 'Intro'))->setTraining($training);
        $second = (new TrainingRoadmapItem(2, 'Atelier'))->setTraining($training);
        $this->setId($first, 101);
        $this->setId($second, 102);
        $training->addRoadmapItem($first)->addRoadmapItem($second);

        $categoryRepository = $this->createMock(TrainingCategoryRepository::class);
        $category = new TrainingCategory('Cybersécurité', 'cyber');
        $this->setId($category, 7);
        $categoryRepository->expects(self::once())->method('findOrdered')->willReturn([$category]);

        $formatter = new TrainingFormatter(
            $this->createMock(TrainingEnrollmentRepository::class),
            new TrainingMetadataFormatter($categoryRepository),
        );

        $payload = $formatter->formatTraining($training);

        self::assertSame(15, $payload['id']);
        self::assertSame(['remote', 'onsite'], $payload['availableFormats']);
        self::assertSame([
            ['value' => 'remote', 'label' => 'Distanciel'],
            ['value' => 'onsite', 'label' => 'Présentiel'],
        ], $payload['availableFormatDetails']);
        self::assertSame(['id' => 7, 'name' => 'Cybersécurité', 'slug' => 'cyber'], $payload['categoryDetails']);
        self::assertFalse($payload['isActive']);
        self::assertSame('Intro', $payload['roadmap'][0]['title']);
        self::assertSame('Atelier', $payload['roadmap'][1]['title']);
    }

    public function testFormatSessionAndEnrollmentHandleUnknownStatusesAndRemainingSeatsFloor(): void
    {
        $training = new Training('Formation cloud', 'formation-cloud', 300, 120000);
        $training->setCategory('general')->setAvailableFormats(['onsite']);
        $session = new TrainingSession(
            $training,
            'hybrid',
            new \DateTimeImmutable('2026-08-10 09:00:00'),
            new \DateTimeImmutable('2026-08-10 17:00:00'),
            2,
        );
        $this->setId($session, 44);
        $session
            ->setDailyStartTime(new \DateTimeImmutable('09:00'))
            ->setDailyEndTime(new \DateTimeImmutable('17:00'))
            ->setIncludeWeekends(false)
            ->setLocation('Paris')
            ->setMeetingUrl('https://meet.example.com')
            ->setStatus('rescheduled');

        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $enrollment = new TrainingEnrollment($session, $user, 120000);
        $this->setId($enrollment, 55);
        $enrollment
            ->setStatus('archived')
            ->setScheduledStartsAt(new \DateTimeImmutable('2026-08-11 09:00:00'))
            ->setScheduledEndsAt(new \DateTimeImmutable('2026-08-11 12:00:00'))
            ->setPaidAt(new \DateTimeImmutable('2026-08-01 12:00:00'))
            ->setStripeSessionId('cs_test_1');

        $enrollments = $this->createMock(TrainingEnrollmentRepository::class);
        $enrollments->expects(self::exactly(2))->method('countActiveForSession')->with($session)->willReturn(5);

        $metadata = new TrainingMetadataFormatter($this->createMock(TrainingCategoryRepository::class));
        $formatter = new TrainingFormatter($enrollments, $metadata);

        $sessionPayload = $formatter->formatSession($session);
        self::assertSame('hybrid', $sessionPayload['format']);
        self::assertSame('hybrid', $sessionPayload['formatLabel']);
        self::assertSame(5, $sessionPayload['enrolledCount']);
        self::assertSame(0, $sessionPayload['remainingSeats']);
        self::assertSame('rescheduled', $sessionPayload['statusLabel']);

        $enrollmentPayload = $formatter->formatEnrollment($enrollment);
        self::assertSame(55, $enrollmentPayload['id']);
        self::assertSame('archived', $enrollmentPayload['status']);
        self::assertSame('archived', $enrollmentPayload['statusLabel']);
        self::assertSame('cs_test_1', $enrollmentPayload['stripeSessionId']);
        self::assertSame(44, $enrollmentPayload['session']['id']);
        self::assertSame(0, $enrollmentPayload['session']['remainingSeats']);
    }

    public function testFormatSessionMapsKnownStatusLabels(): void
    {
        $training = new Training('Formation data', 'formation-data', 180, 80000);
        $session = (new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable('2026-09-10 09:00:00'),
            new \DateTimeImmutable('2026-09-10 17:00:00'),
            10,
        ))
            ->setDailyStartTime(new \DateTimeImmutable('09:00'))
            ->setDailyEndTime(new \DateTimeImmutable('17:00'));

        $enrollments = $this->createMock(TrainingEnrollmentRepository::class);
        $enrollments->method('countActiveForSession')->willReturn(0);
        $formatter = new TrainingFormatter(
            $enrollments,
            new TrainingMetadataFormatter($this->createMock(TrainingCategoryRepository::class)),
        );

        $session->setStatus('scheduled');
        self::assertSame('Planifiée', $formatter->formatSession($session)['statusLabel']);

        $session->setStatus('cancelled');
        self::assertSame('Annulée', $formatter->formatSession($session)['statusLabel']);

        $session->setStatus('completed');
        self::assertSame('Terminée', $formatter->formatSession($session)['statusLabel']);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
