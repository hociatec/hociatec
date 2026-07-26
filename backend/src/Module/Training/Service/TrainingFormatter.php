<?php

declare(strict_types=1);

namespace App\Module\Training\Service;

use App\Module\Training\Entity\Training;
use App\Module\Training\Entity\TrainingEnrollment;
use App\Module\Training\Entity\TrainingRoadmapItem;
use App\Module\Training\Entity\TrainingSession;
use App\Module\Training\Repository\TrainingEnrollmentRepository;

final class TrainingFormatter
{
    public function __construct(
        private readonly TrainingEnrollmentRepository $enrollments,
        private readonly TrainingMetadataFormatter $metadata,
    ) {
    }

    /** @return array<string, mixed> */
    public function formatTraining(Training $training): array
    {
        return [
            'id' => $training->getId(),
            'title' => $training->getTitle(),
            'slug' => $training->getSlug(),
            'shortDescription' => $training->getShortDescription(),
            'objective' => $training->getObjective(),
            'audience' => $training->getAudience(),
            'category' => $training->getCategory(),
            'categoryDetails' => $this->metadata->category($training->getCategory()),
            'durationMinutes' => $training->getDurationMinutes(),
            'priceCents' => $training->getPriceCents(),
            'availableFormats' => $training->getAvailableFormats(),
            'availableFormatDetails' => $this->metadata->formats($training->getAvailableFormats()),
            'isActive' => $training->isActive(),
            'roadmap' => array_map(
                static fn (TrainingRoadmapItem $item): array => [
                    'id' => $item->getId(),
                    'position' => $item->getPosition(),
                    'title' => $item->getTitle(),
                ],
                $training->getRoadmapItems()->toArray(),
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function formatSession(TrainingSession $session): array
    {
        $enrolledCount = $this->enrollments->countActiveForSession($session);

        return [
            'id' => $session->getId(),
            'training' => $this->formatTraining($session->getTraining()),
            'format' => $session->getFormat(),
            'formatLabel' => $this->metadata->formatLabel($session->getFormat()),
            'startsAt' => $session->getStartsAt()->format(\DateTimeImmutable::ATOM),
            'endsAt' => $session->getEndsAt()->format(\DateTimeImmutable::ATOM),
            'dailyStartTime' => $session->getDailyStartTime()->format('H:i'),
            'dailyEndTime' => $session->getDailyEndTime()->format('H:i'),
            'includeWeekends' => $session->includesWeekends(),
            'location' => $session->getLocation(),
            'meetingUrl' => $session->getMeetingUrl(),
            'capacity' => $session->getCapacity(),
            'enrolledCount' => $enrolledCount,
            'remainingSeats' => max(0, $session->getCapacity() - $enrolledCount),
            'status' => $session->getStatus(),
            'statusLabel' => match ($session->getStatus()) {
                'scheduled' => 'Planifiée',
                'cancelled' => 'Annulée',
                'completed' => 'Terminée',
                default => $session->getStatus(),
            },
        ];
    }

    /** @return array<string, mixed> */
    public function formatEnrollment(TrainingEnrollment $enrollment): array
    {
        return [
            'id' => $enrollment->getId(),
            'status' => $enrollment->getStatus(),
            'statusLabel' => $this->metadata->enrollmentStatusLabel($enrollment->getStatus()),
            'priceCents' => $enrollment->getPriceCents(),
            'scheduledStartsAt' => $enrollment->getScheduledStartsAt()->format(\DateTimeImmutable::ATOM),
            'scheduledEndsAt' => $enrollment->getScheduledEndsAt()->format(\DateTimeImmutable::ATOM),
            'paidAt' => $enrollment->getPaidAt()?->format(\DateTimeImmutable::ATOM),
            'stripeSessionId' => $enrollment->getStripeSessionId(),
            'createdAt' => $enrollment->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'session' => $this->formatSession($enrollment->getSession()),
        ];
    }
}
