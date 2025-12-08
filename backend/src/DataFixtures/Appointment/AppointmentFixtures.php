<?php

declare(strict_types=1);

namespace App\DataFixtures\Appointment;

use App\DataFixtures\User\UserFixtures;
use App\Module\Appointment\Entity\Appointment;
use App\Module\Appointment\Entity\Prestation;
use App\Module\User\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppointmentFixtures extends Fixture implements DependentFixtureInterface
{
    private const PAST_APPOINTMENTS_PER_USER = 10;
    private const FUTURE_APPOINTMENTS_PER_USER = 5;

    public function load(ObjectManager $manager): void
    {
        $prestations = $this->collectPrestations();
        $now = new DateTimeImmutable();

        for ($userIndex = 1; $userIndex <= UserFixtures::USER_COUNT; ++$userIndex) {
            /** @var User $user */
            $user = $this->getReference(UserFixtures::getReferenceName($userIndex), User::class);
            $scheduledSlots = [];

            for ($i = 0; $i < self::PAST_APPOINTMENTS_PER_USER; ++$i) {
                $startAt = $this->generateAppointmentDateTime($now, false, $scheduledSlots);
                $prestation = $prestations[random_int(0, count($prestations) - 1)];
                $manager->persist(new Appointment($user, $prestation, $startAt));
            }

            for ($i = 0; $i < self::FUTURE_APPOINTMENTS_PER_USER; ++$i) {
                $startAt = $this->generateAppointmentDateTime($now, true, $scheduledSlots);
                $prestation = $prestations[random_int(0, count($prestations) - 1)];
                $manager->persist(new Appointment($user, $prestation, $startAt));
            }
        }

        $manager->flush();
    }

    /**
     * @return list<Prestation>
     */
    private function collectPrestations(): array
    {
        $prestations = [];

        for ($index = 0; $index < PrestationFixtures::getPrestationCount(); ++$index) {
            /** @var Prestation $prestation */
            $prestation = $this->getReference(PrestationFixtures::getReferenceName($index), Prestation::class);
            $prestations[] = $prestation;
        }

        return $prestations;
    }

    /**
     * @param array<int, string> $scheduledSlots
     */
    private function generateAppointmentDateTime(DateTimeImmutable $now, bool $future, array &$scheduledSlots): DateTimeImmutable
    {
        $attempts = 0;
        $slot = null;
        $minuteOptions = [0, 15, 30, 45];

        while ($attempts < 30) {
            ++$attempts;
            $daysOffset = $future ? random_int(1, 90) : random_int(5, 180);
            $hours = random_int(8, 17);
            $minutes = $minuteOptions[random_int(0, count($minuteOptions) - 1)];

            $date = $future
                ? $now->add(new DateInterval('P' . $daysOffset . 'D'))
                : $now->sub(new DateInterval('P' . $daysOffset . 'D'));
            $date = $date->setTime($hours, $minutes);

            $key = $date->format('Y-m-d H:i');
            if (!in_array($key, $scheduledSlots, true)) {
                $scheduledSlots[] = $key;
                $slot = $date;
                break;
            }
        }

        if ($slot === null) {
            $slot = $future
                ? $now->add(new DateInterval('P1D'))->setTime(10, 0)
                : $now->sub(new DateInterval('P7D'))->setTime(11, 0);
        }

        return $slot;
    }

    /**
     * @return list<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PrestationFixtures::class,
        ];
    }
}
