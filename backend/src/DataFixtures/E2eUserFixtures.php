<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class E2eUserFixtures extends Fixture implements FixtureGroupInterface
{
    public const CLIENT_EMAIL = 'e2e.client@hociatec.local';
    public const ADMIN_EMAIL = 'e2e.admin@hociatec.local';
    public const PASSWORD = 'E2ePassword123';

    public function __construct(
        private readonly UserPasswordHasher $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->upsertUser(
            $manager,
            self::CLIENT_EMAIL,
            ['ROLE_USER'],
            'Client',
            'E2E',
        );

        $this->upsertUser(
            $manager,
            self::ADMIN_EMAIL,
            ['ROLE_ADMIN'],
            'Admin',
            'E2E',
        );

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['e2e'];
    }

    /**
     * @param list<string> $roles
     */
    private function upsertUser(
        ObjectManager $manager,
        string $email,
        array $roles,
        string $firstName,
        string $lastName,
    ): void {
        $user = null;
        foreach ($manager->getRepository(User::class)->findAll() as $candidate) {
            if ($candidate->getEmail() === $email) {
                $user = $candidate;
                break;
            }
        }

        if (!$user instanceof User) {
            $user = new User(
                $email,
                $firstName,
                $lastName,
                new \DateTimeImmutable('1990-01-01'),
                '0600000000',
                'other',
            );
            $manager->persist($user);
        }

        $user
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setPhoneNumber('0600000000')
            ->setBirthDate(new \DateTimeImmutable('1990-01-01'))
            ->setGender('other')
            ->setRoles($roles)
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
    }
}
