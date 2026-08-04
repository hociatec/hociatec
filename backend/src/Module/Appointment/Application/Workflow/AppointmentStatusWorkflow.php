<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Domain\Entity\Appointment;

final class AppointmentStatusWorkflow
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

    /** @return list<string> */
    public function knownStatuses(): array
    {
        return array_keys(self::STATUS_DEFINITIONS);
    }

    public function label(string $status): string
    {
        $status = strtolower($status);

        return self::STATUS_DEFINITIONS[$status]['label'] ?? ucfirst($status);
    }

    public function isKnownStatus(string $status): bool
    {
        return isset(self::STATUS_DEFINITIONS[strtolower($status)]);
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

        if (!in_array($targetStatus, self::STATUS_DEFINITIONS[$currentStatus]['transitions'], true)) {
            return false;
        }

        if (Appointment::STATUS_CANCELLED === $targetStatus) {
            return $appointment->getStartAt() > new \DateTimeImmutable();
        }

        return true;
    }
}
