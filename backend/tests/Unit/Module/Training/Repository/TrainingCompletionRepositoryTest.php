<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Repository;

use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Infrastructure\Repository\TrainingRoadmapItemRepository;
use App\Tests\Unit\Module\Training\TrainingIntegrationTestCase;

final class TrainingCompletionRepositoryTest extends TrainingIntegrationTestCase
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
            ->setScheduledStartsAt(new \DateTimeImmutable(self::ENROLLMENT_START))
            ->setScheduledEndsAt(new \DateTimeImmutable(self::ENROLLMENT_END));
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
            new \DateTimeImmutable('2026-08-12T09:30:00+00:00'),
            new \DateTimeImmutable('2026-08-12T10:30:00+00:00'),
        ));
        self::assertSame($enrollment, $enrollments->findOneForUserAndSession($user, $session));
        self::assertSame($enrollment, $enrollments->findOneByStripeSessionId('cs_test'));
        self::assertSame([$enrollment], $enrollments->findForUser($user));
        self::assertInstanceOf(TrainingRoadmapItemRepository::class, $this->roadmapRepository($em));
    }
}
