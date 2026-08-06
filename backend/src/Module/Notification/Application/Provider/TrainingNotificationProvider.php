<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Provider;

use App\Module\Notification\Application\Notification\ComputedAccountNotificationProviderInterface;
use App\Module\Notification\Application\Projection\AccountNotificationFormatter;
use App\Module\Training\Application\Port\TrainingEnrollmentRepositoryPort;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\User\Domain\Entity\User;

final readonly class TrainingNotificationProvider implements ComputedAccountNotificationProviderInterface
{
    public function __construct(
        private TrainingEnrollmentRepositoryPort $trainingEnrollments,
        private AccountNotificationFormatter $formatter,
    ) {
    }

    public function provide(User $user, \DateTimeImmutable $now): array
    {
        $nextTraining = $this->nextTraining($user, $now);
        if (null === $nextTraining) {
            return [];
        }

        $trainingTitle = $nextTraining->getSession()->getTraining()->getTitle();

        return [
            $this->formatter->computedNotification(
                'training:'.$nextTraining->getId().':'.$nextTraining->getScheduledStartsAt()->format(DATE_ATOM),
                'Formation '.$trainingTitle.' le '.$this->formatter->formatFrenchDateTime($nextTraining->getScheduledStartsAt()),
                'Votre formation '.$trainingTitle.' est prévue le '.$this->formatter->formatFrenchDateTime($nextTraining->getScheduledStartsAt()).'.',
                '/trainings/me/'.$nextTraining->getId(),
                'training_reminder',
                $now,
            ),
        ];
    }

    private function nextTraining(User $user, \DateTimeImmutable $now): ?TrainingEnrollment
    {
        $enrollments = array_filter(
            $this->trainingEnrollments->findForUser($user),
            static fn (TrainingEnrollment $enrollment): bool => TrainingEnrollment::STATUS_CANCELLED !== $enrollment->getStatus()
                && $enrollment->getScheduledStartsAt() >= $now,
        );
        usort(
            $enrollments,
            static fn (TrainingEnrollment $left, TrainingEnrollment $right): int => $left->getScheduledStartsAt() <=> $right->getScheduledStartsAt(),
        );

        return $enrollments[0] ?? null;
    }
}
