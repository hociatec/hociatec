<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Centralizes all appointment status transitions and metadata so we have a
 * single place to maintain business rules.
 */
final class AppointmentStatusManager
{
    /**
     * @var array<string, array{label: string, transitions: list<string>}>
     */
    private const STATUS_DEFINITIONS = [
        Appointment::STATUS_CONFIRMED => [
            'label' => 'Confirmé',
            'transitions' => [Appointment::STATUS_CANCELLED],
        ],
        Appointment::STATUS_CANCELLED => [
            'label' => 'Annulé',
            'transitions' => [Appointment::STATUS_CONFIRMED],
        ],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return list<string>
     */
    public function getKnownStatuses(): array
    {
        return array_keys(self::STATUS_DEFINITIONS);
    }

    public function getLabel(string $status): string
    {
        $status = strtolower($status);

        return self::STATUS_DEFINITIONS[$status]['label'] ?? ucfirst($status);
    }

    public function isKnownStatus(string $status): bool
    {
        $status = strtolower($status);

        return isset(self::STATUS_DEFINITIONS[$status]);
    }

    public function canBeCancelled(Appointment $appointment): bool
    {
        return $this->canTransition($appointment, Appointment::STATUS_CANCELLED);
    }

    public function canTransition(Appointment $appointment, string $targetStatus): bool
    {
        $targetStatus = strtolower($targetStatus);
        $currentStatus = $appointment->getStatus();

        if (!isset(self::STATUS_DEFINITIONS[$targetStatus]) || !isset(self::STATUS_DEFINITIONS[$currentStatus])) {
            return false;
        }

        if ($currentStatus === $targetStatus) {
            return false;
        }

        $allowedTransitions = self::STATUS_DEFINITIONS[$currentStatus]['transitions'];

        if (!in_array($targetStatus, $allowedTransitions, true)) {
            return false;
        }

        if (Appointment::STATUS_CANCELLED === $targetStatus) {
            return $appointment->getStartAt() > new \DateTimeImmutable();
        }

        return true;
    }

    public function changeStatus(Appointment $appointment, string $targetStatus): void
    {
        $targetStatus = strtolower($targetStatus);

        if (!isset(self::STATUS_DEFINITIONS[$targetStatus])) {
            throw new \DomainException('Statut de rendez-vous inconnu.');
        }

        if ($appointment->getStatus() === $targetStatus) {
            return;
        }

        if (!$this->canTransition($appointment, $targetStatus)) {
            throw new \DomainException('Transition de statut impossible pour ce rendez-vous.');
        }

        $appointment->setStatus($targetStatus);
        $this->entityManager->flush();
    }
}
