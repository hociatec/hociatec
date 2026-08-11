<?php

declare(strict_types=1);

namespace App\Module\Appointment\Application\Workflow;

use App\Module\Appointment\Application\Exception\AppointmentOperationException;
use App\Module\Appointment\Application\Port\PrestationPersistencePort;
use App\Module\Appointment\Application\Port\PrestationRepositoryPort;
use App\Module\Appointment\Domain\Entity\Prestation;

final class PrestationService
{
    public function __construct(
        private readonly PrestationRepositoryPort $prestationRepository,
        private readonly PrestationPersistencePort $persistence,
    ) {
    }

    /**
     * @return list<Prestation>
     */
    public function list(int $limit = 50, int $offset = 0): array
    {
        return $this->prestationRepository->findAllOrderedByName($limit, $offset);
    }

    public function count(): int
    {
        return $this->prestationRepository->countAll();
    }

    public function create(string $name, int $durationMinutes, int $priceCents): Prestation
    {
        $this->assertValidData($name, $durationMinutes, $priceCents);

        $prestation = new Prestation($name, $durationMinutes, $priceCents);

        try {
            $this->persistence->save($prestation);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible d\'enregistrer la prestation.', $exception);
        }

        return $prestation;
    }

    public function update(Prestation $prestation, string $name, int $durationMinutes, int $priceCents): Prestation
    {
        $this->assertValidData($name, $durationMinutes, $priceCents);

        $prestation
            ->setName($name)
            ->setDurationMinutes($durationMinutes)
            ->setPriceCents($priceCents);

        try {
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible de mettre à jour la prestation.', $exception);
        }

        return $prestation;
    }

    public function delete(Prestation $prestation): void
    {
        try {
            $this->persistence->delete($prestation);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw AppointmentOperationException::failed('Impossible de supprimer la prestation.', $exception);
        }
    }

    private function assertValidData(string $name, int $durationMinutes, int $priceCents): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('La prestation doit avoir un nom.');
        }
        if (mb_strlen($name) > 120) {
            throw new \InvalidArgumentException('Le nom ne doit pas depasser 120 caracteres.');
        }
        if ($durationMinutes <= 0) {
            throw new \InvalidArgumentException('La duree doit etre superieure a 0.');
        }
        if ($durationMinutes > 8 * 60) {
            throw new \InvalidArgumentException('La duree ne peut depasser 8 heures.');
        }
        if ($priceCents < 0) {
            throw new \InvalidArgumentException('Le prix doit etre positif.');
        }
        if ($priceCents > 1000000) {
            throw new \InvalidArgumentException('Le prix est trop eleve.');
        }
    }
}
