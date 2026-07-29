<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\User\Repository;

use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class ShippingAddressRepositoryTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testRepositoryPersistsQueriesDefaultsAndRemovesAddresses(): void
    {
        $entityManager = $this->entityManager();
        $user = $this->user('ada@example.com');
        $other = $this->user('grace@example.com');
        $entityManager->persist($user);
        $entityManager->persist($other);
        $entityManager->flush();

        $repository = $this->repository($entityManager);
        $home = (new ShippingAddress($user, 'Home', '1 rue A', '75001', 'Paris'))->setIsDefault(true);
        $office = new ShippingAddress($user, 'Office', '2 rue B', '69000', 'Lyon');
        $otherAddress = new ShippingAddress($other, 'Other', '3 rue C', '13000', 'Marseille');

        $repository->save($home);
        $repository->save($office);
        $repository->save($otherAddress, true);

        self::assertCount(2, $repository->findAllForUser($user));
        self::assertSame($home->getId(), $repository->findDefaultForUser($user)?->getId());
        self::assertSame($home->getId(), $repository->findFirstForUser($user)?->getId());
        self::assertSame($office->getId(), $repository->findOneForUser((int) $office->getId(), $user)?->getId());
        self::assertNull($repository->findOneForUser((int) $otherAddress->getId(), $user));

        $repository->setDefault($user, $office);
        $entityManager->refresh($home);
        $entityManager->refresh($office);
        self::assertFalse($home->isDefault());
        self::assertTrue($office->isDefault());

        $homeId = $home->getId();
        $repository->remove($home, true);
        self::assertNull($entityManager->find(ShippingAddress::class, $homeId));
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        $tool = new SchemaTool($entityManager);
        $tool->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(ShippingAddress::class),
        ]);
        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function repository(EntityManager $entityManager): ShippingAddressRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new ShippingAddressRepository($registry);
    }

    private function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }
}
