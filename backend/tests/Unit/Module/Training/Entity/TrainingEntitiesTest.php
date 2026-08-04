<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Entity;

use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class TrainingEntitiesTest extends TestCase
{
    public function testTrainingAndRoadmapMutators(): void
    {
        $training = new Training('Title', 'slug', 90, 10000);
        $updatedAt = $training->getUpdatedAt();

        self::assertNull($training->getId());
        $item = new TrainingRoadmapItem(1, 'Step 1');
        self::assertNull($item->getId());
        $training
            ->setTitle('New title')
            ->setSlug('new-slug')
            ->setShortDescription('Short')
            ->setObjective('Objective')
            ->setAudience('Audience')
            ->setCategory('  ')
            ->setDurationMinutes(120)
            ->setPriceCents(12000)
            ->setAvailableFormats(['onsite'])
            ->setIsActive(false)
            ->addRoadmapItem($item);

        self::assertSame('New title', $training->getTitle());
        self::assertSame('new-slug', $training->getSlug());
        self::assertSame('Short', $training->getShortDescription());
        self::assertSame('Objective', $training->getObjective());
        self::assertSame('Audience', $training->getAudience());
        self::assertSame('general', $training->getCategory());
        self::assertSame(120, $training->getDurationMinutes());
        self::assertSame(12000, $training->getPriceCents());
        self::assertSame(['onsite'], $training->getAvailableFormats());
        self::assertFalse($training->isActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $training->getCreatedAt());
        self::assertCount(1, $training->getRoadmapItems());
        self::assertSame($training, $item->getTraining());
        self::assertSame('Step 1', $item->getTitle());

        $item->setPosition(2)->setTitle('Step 2');
        self::assertSame(2, $item->getPosition());
        self::assertSame('Step 2', $item->getTitle());

        $training->clearRoadmapItems();
        self::assertCount(0, $training->getRoadmapItems());

        usleep(1000);
        $training->touch();
        self::assertGreaterThanOrEqual($updatedAt, $training->getUpdatedAt());
    }

    public function testTrainingCategorySessionAndEnrollmentMutators(): void
    {
        $category = new TrainingCategory('Cat', 'cat');
        $categoryUpdatedAt = $category->getUpdatedAt();
        self::assertNull($category->getId());
        $category
            ->setName('Category')
            ->setSlug('category')
            ->setPosition(3)
            ->setIsActive(false);

        self::assertSame('Category', $category->getName());
        self::assertSame('category', $category->getSlug());
        self::assertSame(3, $category->getPosition());
        self::assertFalse($category->isActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());

        usleep(1000);
        $category->touch();
        self::assertGreaterThanOrEqual($categoryUpdatedAt, $category->getUpdatedAt());

        $training = new Training('Title', 'slug', 90, 10000);
        $session = new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable('2026-08-01T09:00:00+00:00'),
            new \DateTimeImmutable('2026-08-01T17:00:00+00:00'),
            10,
        );
        $sessionUpdatedAt = $session->getUpdatedAt();
        self::assertNull($session->getId());
        $session
            ->setTraining($training)
            ->setFormat('onsite')
            ->setStartsAt(new \DateTimeImmutable('2026-08-02T09:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2026-08-02T17:00:00+00:00'))
            ->setDailyStartTime(new \DateTimeImmutable('09:00'))
            ->setDailyEndTime(new \DateTimeImmutable('18:00'))
            ->setIncludeWeekends(false)
            ->setLocation('Paris')
            ->setMeetingUrl('https://meet.example.com')
            ->setCapacity(12)
            ->setStatus('open');

        self::assertSame($training, $session->getTraining());
        self::assertSame('onsite', $session->getFormat());
        self::assertSame('2026-08-02T09:00:00+00:00', $session->getStartsAt()->format(DATE_ATOM));
        self::assertSame('2026-08-02T17:00:00+00:00', $session->getEndsAt()->format(DATE_ATOM));
        self::assertSame('09:00', $session->getDailyStartTime()->format('H:i'));
        self::assertSame('18:00', $session->getDailyEndTime()->format('H:i'));
        self::assertFalse($session->includesWeekends());
        self::assertSame('Paris', $session->getLocation());
        self::assertSame('https://meet.example.com', $session->getMeetingUrl());
        self::assertSame(12, $session->getCapacity());
        self::assertSame('open', $session->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $session->getCreatedAt());

        usleep(1000);
        $session->touch();
        self::assertGreaterThanOrEqual($sessionUpdatedAt, $session->getUpdatedAt());

        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $enrollment = new TrainingEnrollment($session, $user, 10000);
        self::assertNull($enrollment->getId());
        self::assertSame(TrainingEnrollment::STATUS_PENDING_PAYMENT, $enrollment->getStatus());
        self::assertSame($session, $enrollment->getSession());
        self::assertSame($user, $enrollment->getUser());
        self::assertSame(10000, $enrollment->getPriceCents());
        self::assertSame($session->getStartsAt(), $enrollment->getScheduledStartsAt());
        self::assertSame($session->getEndsAt(), $enrollment->getScheduledEndsAt());
        self::assertNull($enrollment->getPaidAt());
        self::assertNull($enrollment->getStripeSessionId());
        self::assertNull($enrollment->getStripePaymentIntentId());
        self::assertInstanceOf(\DateTimeImmutable::class, $enrollment->getCreatedAt());
    }
}
