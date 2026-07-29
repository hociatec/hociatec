<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Repository;

use App\Module\Order\Entity\Order;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\User\Entity\User;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class QuoteRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([
            $this->entityManager->getClassMetadata(User::class),
            $this->entityManager->getClassMetadata(Order::class),
            $this->entityManager->getClassMetadata(Quote::class),
        ]);
    }

    public function testQuoteRepositoryQueries(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $order = new Order('ORD-1', $user);
        $quoteA = (new Quote('Q-2026-001'))
            ->setCustomerName('Ada Lovelace')
            ->setCustomerEmail('ada@example.com')
            ->setStatus(Quote::STATUS_ACCEPTED);
        $quoteB = (new Quote('Q-2026-002'))
            ->setCustomerName('Grace Hopper')
            ->setCustomerEmail('grace@example.com')
            ->setStatus(Quote::STATUS_SENT)
            ->setCreatedEmailSentAt(new \DateTimeImmutable('2026-07-21T10:00:00+00:00'));
        $quoteC = (new Quote('Q-2025-001'))
            ->setCustomerName('Old Quote')
            ->setStatus(Quote::STATUS_ACCEPTED)
            ->setConvertedOrder($order)
            ->setCreatedEmailSentAt(new \DateTimeImmutable('2026-07-22T10:00:00+00:00'));

        $this->setDate($quoteA, 'createdAt', new \DateTimeImmutable('2026-01-10T10:00:00+00:00'));
        $this->setDate($quoteA, 'updatedAt', new \DateTimeImmutable('2026-07-20T10:00:00+00:00'));
        $this->setDate($quoteB, 'createdAt', new \DateTimeImmutable('2026-02-10T10:00:00+00:00'));
        $this->setDate($quoteB, 'updatedAt', new \DateTimeImmutable('2026-07-21T10:00:00+00:00'));
        $this->setDate($quoteC, 'createdAt', new \DateTimeImmutable('2025-03-10T10:00:00+00:00'));
        $this->setDate($quoteC, 'updatedAt', new \DateTimeImmutable('2026-07-22T10:00:00+00:00'));

        foreach ([$user, $order, $quoteA, $quoteB, $quoteC] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $repository = $this->repository();

        self::assertSame(2, $repository->countForYear(2026));
        self::assertCount(1, $repository->findBySearch('Ada', null));
        self::assertCount(1, $repository->findBySearch(null, Quote::STATUS_SENT));
        self::assertCount(3, $repository->findBySearch(null, 'all'));
        self::assertSame([$quoteA->getId()], array_map(static fn (Quote $q): ?int => $q->getId(), $repository->findAcceptedWaitingForConversion()));
        self::assertCount(3, $repository->findRecentByStatuses([Quote::STATUS_ACCEPTED, Quote::STATUS_SENT], 5));
        $emailed = $repository->findRecentlyEmailed(2);
        self::assertCount(2, $emailed);
        self::assertSame($quoteC->getId(), $emailed[0]->getId());
    }

    private function repository(): QuoteRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new QuoteRepository($registry);
    }

    private function setDate(object $entity, string $property, \DateTimeImmutable $value): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty($property)->setValue($entity, $value);
    }
}
