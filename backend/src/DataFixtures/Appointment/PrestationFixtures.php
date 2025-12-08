<?php

declare(strict_types=1);

namespace App\DataFixtures\Appointment;

use App\Module\Appointment\Entity\Prestation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PrestationFixtures extends Fixture
{
    public const REFERENCE_PREFIX = 'appointment_prestation_';

    /**
     * @var list<array{name: string, duration: int, price: int}>
     */
    private const PRESTATIONS = [
        ['name' => 'Diagnostic PC complet', 'duration' => 60, 'price' => 4900],
        ['name' => 'Installation systeme et pilotes', 'duration' => 90, 'price' => 7500],
        ['name' => 'Optimisation gaming desktop', 'duration' => 75, 'price' => 6900],
        ['name' => 'Migration de donnees', 'duration' => 80, 'price' => 7200],
        ['name' => 'Depannage express smartphone', 'duration' => 45, 'price' => 4200],
        ['name' => 'Nettoyage thermique PC portable', 'duration' => 70, 'price' => 6100],
        ['name' => 'Installation reseau et nas', 'duration' => 120, 'price' => 9900],
        ['name' => 'Audit parc informatique', 'duration' => 180, 'price' => 14900],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRESTATIONS as $index => $data) {
            $prestation = new Prestation($data['name'], $data['duration'], $data['price']);
            $manager->persist($prestation);

            $this->addReference(self::REFERENCE_PREFIX . str_pad((string) $index, 2, '0', STR_PAD_LEFT), $prestation);
        }

        $manager->flush();
    }

    public static function getReferenceName(int $index): string
    {
        return self::REFERENCE_PREFIX . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }

    public static function getPrestationCount(): int
    {
        return count(self::PRESTATIONS);
    }
}
