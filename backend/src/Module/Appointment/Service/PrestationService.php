<?php

declare(strict_types=1);

namespace App\Module\Appointment\Service;

use App\Module\Appointment\Entity\Prestation;
use App\Module\Appointment\Repository\PrestationRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PrestationService
{
    public function __construct(
        private readonly PrestationRepository $prestationRepository,
        private readonly PrestationPersistence $persistence,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return list<Prestation>
     */
    public function list(): array
    {
        return $this->prestationRepository->findAllOrderedByName();
    }

    public function create(string $name, int $durationMinutes, int $priceCents): Prestation
    {
        $this->assertValidData($name, $durationMinutes, $priceCents);

        $prestation = new Prestation($name, $durationMinutes, $priceCents);

        $this->persistence->save($prestation);

        return $prestation;
    }

    public function update(Prestation $prestation, string $name, int $durationMinutes, int $priceCents): Prestation
    {
        $this->assertValidData($name, $durationMinutes, $priceCents);

        $prestation
            ->setName($name)
            ->setDurationMinutes($durationMinutes)
            ->setPriceCents($priceCents);

        $this->persistence->flush();

        return $prestation;
    }

    public function delete(Prestation $prestation): void
    {
        $this->persistence->delete($prestation);
    }

    private function assertValidData(string $name, int $durationMinutes, int $priceCents): void
    {
        $violations = $this->validator->validate(
            [
                'name' => $name,
                'duration' => $durationMinutes,
                'price' => $priceCents,
            ],
            new Assert\Collection([
                'name' => [
                    new Assert\NotBlank(message: 'La prestation doit avoir un nom.'),
                    new Assert\Length(max: 120, maxMessage: 'Le nom ne doit pas depasser 120 caracteres.'),
                ],
                'duration' => [
                    new Assert\Positive(message: 'La duree doit etre superieure a 0.'),
                    new Assert\LessThanOrEqual(value: 8 * 60, message: 'La duree ne peut depasser 8 heures.'),
                ],
                'price' => [
                    new Assert\GreaterThanOrEqual(value: 0, message: 'Le prix doit etre positif.'),
                    new Assert\LessThanOrEqual(value: 1000000, message: 'Le prix est trop eleve.'),
                ],
            ])
        );

        if ($violations->count() > 0) {
            throw new \InvalidArgumentException((string) $violations);
        }
    }
}
