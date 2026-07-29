<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Repository;

use App\Module\Training\Entity\TrainingCategory;
use App\Module\Training\Repository\TrainingCategoryRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class TrainingCategoryRepositoryTest extends TestCase
{
    private EntityManager $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->entityManager = new EntityManager($connection, $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema([$this->entityManager->getClassMetadata(TrainingCategory::class)]);
    }

    public function testFindOrderedSupportsActiveOnly(): void
    {
        $catA = (new TrainingCategory('Cloud', 'cloud'))->setPosition(2);
        $catB = (new TrainingCategory('Infra', 'infra'))->setPosition(1);
        $catC = (new TrainingCategory('Legacy', 'legacy'))->setPosition(1)->setIsActive(false);

        foreach ([$catA, $catB, $catC] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        $repository = $this->repository();

        self::assertSame(['Infra', 'Legacy', 'Cloud'], array_map(static fn (TrainingCategory $c): string => $c->getName(), $repository->findOrdered()));
        self::assertSame(['Infra', 'Cloud'], array_map(static fn (TrainingCategory $c): string => $c->getName(), $repository->findOrdered(true)));
    }

    private function repository(): TrainingCategoryRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        return new TrainingCategoryRepository($registry);
    }
}
