<?php

declare(strict_types=1);

namespace App\DataFixtures\User;

use App\Module\User\Entity\User;
use App\Module\User\Entity\ShippingAddress;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_REFERENCE_PREFIX = 'user_';
    public const USER_COUNT = 100;

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $firstNames = [
            'Emma', 'Noah', 'Jules', 'Lina', 'Adam', 'Mila', 'Leo', 'Nora', 'Ethan', 'Ava',
            'Oscar', 'Iris', 'Hugo', 'Lola', 'Eliot', 'Sofia', 'Marius', 'Clara', 'Max', 'Zoe',
        ];
        $lastNames = [
            'Martin', 'Bernard', 'Robert', 'Richard', 'Petit', 'Durand', 'Dubois', 'Moreau', 'Laurent', 'Simon',
            'Michel', 'Lefebvre', 'Leroy', 'Roux', 'David', 'Bertrand', 'Morel', 'Fournier', 'Girard', 'Andre',
        ];
        $streetNames = [
            'des Lilas', 'des Fleurs', 'des Artisans', 'du Parc', 'Victor Hugo', 'des Peupliers', 'de la Gare',
            'Saint Michel', 'du Moulin', 'des Tilleuls', 'de la Source', 'des Jardins', 'de la Cite', 'des Pres',
        ];
        $cities = [
            'Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Bordeaux', 'Lille',
            'Rennes', 'Reims', 'Le Havre', 'Saint Etienne', 'Toulon',
        ];
        $genders = ['male', 'female', 'other'];

        for ($i = 1; $i <= self::USER_COUNT; ++$i) {
            $firstName = $firstNames[random_int(0, count($firstNames) - 1)];
            $lastName = $lastNames[random_int(0, count($lastNames) - 1)];
            $email = sprintf('user%03d@example.com', $i);
            $address = sprintf('%d rue %s', random_int(1, 199), $streetNames[random_int(0, count($streetNames) - 1)]);
            $postalCode = sprintf('%05d', random_int(10000, 95999));
            $city = $cities[random_int(0, count($cities) - 1)];
            $gender = $genders[random_int(0, count($genders) - 1)];

            $ageYears = random_int(22, 65);
            $extraDays = random_int(0, 364);
            $birthDate = (new DateTimeImmutable())
                ->sub(new DateInterval('P' . $ageYears . 'Y'))
                ->sub(new DateInterval('P' . $extraDays . 'D'));

            $user = new User(
                $email,
                $firstName,
                $lastName,
                $birthDate,
                $this->generatePhoneNumber(),
                $gender,
            );

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'ChangeMe123!');
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            // create initial address
            $shipping = new ShippingAddress($user, $firstName . ' ' . $lastName, $address, $postalCode, $city);
            $shipping->setIsDefault(true);
            $manager->persist($shipping);
            $this->addReference(self::getReferenceName($i), $user);
        }

        $manager->flush();
    }

    private function generatePhoneNumber(): string
    {
        $number = '06';
        for ($i = 0; $i < 4; ++$i) {
            $number .= ' ' . sprintf('%02d', random_int(0, 99));
        }

        return $number;
    }

    public static function getReferenceName(int $index): string
    {
        return self::USER_REFERENCE_PREFIX . str_pad((string) $index, 3, '0', STR_PAD_LEFT);
    }
}
