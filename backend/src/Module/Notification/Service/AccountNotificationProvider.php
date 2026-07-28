<?php

declare(strict_types=1);

namespace App\Module\Notification\Service;

use App\Module\Appointment\Entity\Appointment;
use App\Module\Appointment\Service\AppointmentService;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\Audit\Service\AuditMetadataFormatter;
use App\Module\Rating\Service\PendingReviewResolver;
use App\Module\Training\Entity\TrainingEnrollment;
use App\Module\Training\Repository\TrainingEnrollmentRepository;
use App\Module\Notification\Entity\AccountNotificationEvent;
use App\Module\User\Entity\User;
use App\Module\Notification\Repository\AccountNotificationEventRepository;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Repository\VoucherRepository;

final readonly class AccountNotificationProvider
{
    public function __construct(
        private AccountNotificationEventRepository $events,
        private PendingReviewResolver $pendingReviews,
        private AppointmentService $appointments,
        private TrainingEnrollmentRepository $trainingEnrollments,
        private AuditRequestRepository $audits,
        private AuditMetadataFormatter $auditMetadata,
        private VoucherRepository $vouchers,
    ) {
    }

    /**
     * @return list<array{key: string, label: string, message: string, to: string, type: string, createdAt: string}>
     */
    public function provideForUser(User $user, int $limit = 30, int $offset = 0): array
    {
        if (!in_array(CommunicationPreferences::NOTIFICATION, $user->getCommunicationPreferences(), true)) {
            return [];
        }

        $computed = 0 === $offset ? $this->buildComputedNotifications($user) : [];

        return [
            ...$computed,
            ...array_map($this->formatEvent(...), $this->events->findRecentForUser($user, max(1, $limit - count($computed)), $offset)),
        ];
    }

    public function countForUser(User $user): int
    {
        if (!in_array(CommunicationPreferences::NOTIFICATION, $user->getCommunicationPreferences(), true)) {
            return 0;
        }

        return $this->events->countForUser($user) + count($this->buildComputedNotifications($user));
    }

    /**
     * @return array{key: string, label: string, message: string, to: string, type: string, createdAt: string}
     */
    private function formatEvent(AccountNotificationEvent $notification): array
    {
        return [
            'key' => $notification->getKey(),
            'label' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'to' => $this->safeInternalTarget($notification->getTargetUrl()),
            'type' => $notification->getType(),
            'createdAt' => $notification->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return list<array{key: string, label: string, message: string, to: string, type: string, createdAt: string}>
     */
    private function buildComputedNotifications(User $user): array
    {
        $now = new \DateTimeImmutable();
        $notifications = [];

        $pendingReviews = $this->pendingReviews->resolve($user);
        if ([] !== $pendingReviews) {
            $orderItemIds = array_map(
                static fn (array $review): int => (int) ($review['orderItemId'] ?? 0),
                $pendingReviews,
            );
            sort($orderItemIds);
            $firstOrderId = (int) ($pendingReviews[0]['orderId'] ?? 0);
            $count = count($pendingReviews);
            $notifications[] = $this->computedNotification(
                'reviews:'.implode(',', $orderItemIds),
                $count.' avis produit'.($count > 1 ? 's' : '').' à laisser',
                'Vous avez '.$count.' avis produit'.($count > 1 ? 's' : '').' à laisser sur une commande livrée.',
                $firstOrderId > 0 ? '/orders/'.$firstOrderId : '/orders/me',
                'pending_reviews',
                $now,
            );
        }

        $nextAppointment = $this->nextAppointment($user, $now);
        if (null !== $nextAppointment) {
            $notifications[] = $this->computedNotification(
                'appointment:'.$nextAppointment->getId().':'.$nextAppointment->getStartAt()->format(DATE_ATOM),
                'Prochain rendez-vous le '.$this->formatFrenchDateTime($nextAppointment->getStartAt()),
                'Un rendez-vous est planifié le '.$this->formatFrenchDateTime($nextAppointment->getStartAt()).'.',
                '/appointments/me',
                'appointment_reminder',
                $now,
            );
        }

        $nextTraining = $this->nextTraining($user, $now);
        if (null !== $nextTraining) {
            $trainingTitle = $nextTraining->getSession()->getTraining()->getTitle();
            $notifications[] = $this->computedNotification(
                'training:'.$nextTraining->getId().':'.$nextTraining->getScheduledStartsAt()->format(DATE_ATOM),
                'Formation '.$trainingTitle.' le '.$this->formatFrenchDateTime($nextTraining->getScheduledStartsAt()),
                'Votre formation '.$trainingTitle.' est prévue le '.$this->formatFrenchDateTime($nextTraining->getScheduledStartsAt()).'.',
                '/trainings/me/'.$nextTraining->getId(),
                'training_reminder',
                $now,
            );
        }

        $activeAudit = $this->activeAudit($user);
        if (null !== $activeAudit) {
            $statusLabel = $this->auditMetadata->statusLabel($activeAudit->getStatus());
            $notifications[] = $this->computedNotification(
                'audit:'.$activeAudit->getId().':'.$activeAudit->getStatus(),
                'Audit '.$statusLabel.' en suivi',
                'Votre audit '.$activeAudit->getNumber().' est actuellement à l’état : '.$statusLabel.'.',
                '/audits/me/'.$activeAudit->getId(),
                'audit_follow_up',
                $now,
            );
        }

        $usableVouchers = $this->usableVouchers($user, $now);
        if ([] !== $usableVouchers) {
            $voucherIds = array_map(static fn (Voucher $voucher): int => (int) $voucher->getId(), $usableVouchers);
            sort($voucherIds);
            $count = count($usableVouchers);
            $notifications[] = $this->computedNotification(
                'vouchers:'.implode(',', $voucherIds),
                $count.' bon'.($count > 1 ? 's' : '').' disponible'.($count > 1 ? 's' : ''),
                'Vous avez '.$count.' bon'.($count > 1 ? 's' : '').' utilisable'.($count > 1 ? 's' : '').' sur votre compte.',
                '/vouchers/me',
                'voucher_available',
                $now,
            );
        }

        return $notifications;
    }

    private function nextAppointment(User $user, \DateTimeImmutable $now): ?Appointment
    {
        $upcoming = $this->appointments->getAppointmentsForUser($user)['upcoming'];
        usort($upcoming, static fn (Appointment $left, Appointment $right): int => $left->getStartAt() <=> $right->getStartAt());

        foreach ($upcoming as $appointment) {
            if (!$appointment->isCancelled() && $appointment->getStartAt() >= $now) {
                return $appointment;
            }
        }

        return null;
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

    private function activeAudit(User $user): ?AuditRequest
    {
        $audits = $this->audits->findByUser($user);
        foreach ($audits as $audit) {
            if (AuditRequest::STATUS_DONE !== $audit->getStatus()) {
                return $audit;
            }
        }

        return $audits[0] ?? null;
    }

    /**
     * @return list<Voucher>
     */
    private function usableVouchers(User $user, \DateTimeImmutable $now): array
    {
        $userId = $user->getId();
        if (null === $userId) {
            return [];
        }

        return array_values(array_filter(
            $this->vouchers->findByRecipientUserId($userId),
            static fn (Voucher $voucher): bool => $voucher->isActive()
                && (null === $voucher->getStartsAt() || $voucher->getStartsAt() <= $now)
                && (null === $voucher->getEndsAt() || $voucher->getEndsAt() >= $now),
        ));
    }

    /**
     * @return array{key: string, label: string, message: string, to: string, type: string, createdAt: string}
     */
    private function computedNotification(string $key, string $label, string $message, string $to, string $type, \DateTimeImmutable $createdAt): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'message' => $message,
            'to' => $this->safeInternalTarget($to),
            'type' => $type,
            'createdAt' => $createdAt->format(DATE_ATOM),
        ];
    }

    private function safeInternalTarget(string $target): string
    {
        $target = trim($target);
        if (!str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return '/mon-espace';
        }

        return $target;
    }

    private function formatFrenchDateTime(\DateTimeImmutable $date): string
    {
        return $date->format('d/m/Y H:i');
    }
}
