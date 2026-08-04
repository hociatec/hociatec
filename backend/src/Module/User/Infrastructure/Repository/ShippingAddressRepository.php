<?php

declare(strict_types=1);

namespace App\Module\User\Infrastructure\Repository;

use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingAddress>
 */
class ShippingAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingAddress::class);
    }

    public function save(ShippingAddress $address, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->persist($address);
        if ($flush) {
            $em->flush();
        }
    }

    public function remove(ShippingAddress $address, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->remove($address);
        if ($flush) {
            $em->flush();
        }
    }

    public function delete(ShippingAddress $address): void
    {
        $this->remove($address, true);
    }

    /** @return list<ShippingAddress> */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.isDefault', 'DESC')
            ->addOrderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?ShippingAddress
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFirstForUser(User $user): ?ShippingAddress
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultForUser(User $user): ?ShippingAddress
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.isDefault = :d')
            ->setParameter('user', $user)
            ->setParameter('d', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function setDefault(User $user, ShippingAddress $address): void
    {
        $em = $this->getEntityManager();
        // unset others
        $em->createQuery('UPDATE '.ShippingAddress::class.' a SET a.isDefault = false WHERE a.user = :user')
            ->setParameter('user', $user)
            ->execute();
        // set the one
        $address->setIsDefault(true);
        $em->persist($address);
        $em->flush();
    }
}
