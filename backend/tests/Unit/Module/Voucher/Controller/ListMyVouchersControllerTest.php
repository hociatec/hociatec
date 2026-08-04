<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Voucher\Controller;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\UI\Controller\ListMyVouchersController;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class ListMyVouchersControllerTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testListMyVouchersControllerReturnsOnlyCurrentUserItems(): void
    {
        $repository = $this->repository($this->entityManager());
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 42);

        $mine = (new Voucher('Gift', 'GIFT10', Voucher::TYPE_FIXED_CENTS, 1000))
            ->setRecipientUserId(42)
            ->setRecipientEmail('ada@example.com');
        $other = (new Voucher('Other', 'OTHER10', Voucher::TYPE_PERCENT, 10))
            ->setRecipientUserId(7);

        $repository->save($mine);
        $repository->save($other, true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('security.token_storage')->willReturn(true);
        $container->method('get')->with('security.token_storage')->willReturn($storage);

        $controller = new ListMyVouchersController($repository);
        $controller->setContainer($container);

        $payload = json_decode((string) $controller->__invoke()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['data']['items']);
        self::assertSame('GIFT10', $payload['data']['items'][0]['code']);
        self::assertSame(42, $payload['data']['items'][0]['recipientUserId']);
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
            $entityManager->getClassMetadata(Voucher::class),
        ]);

        $this->entityManager = $entityManager;

        return $entityManager;
    }

    private function repository(EntityManager $entityManager): VoucherRepository
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);

        return new VoucherRepository($registry);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
