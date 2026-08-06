<?php

declare(strict_types=1);

namespace App\Module\News\Infrastructure\Repository;

use App\Module\News\Application\Port\NewsCommentRepositoryPort;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsComment;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NewsComment> */
final class NewsCommentRepository extends ServiceEntityRepository implements NewsCommentRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsComment::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?NewsComment
    {
        $comment = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $comment instanceof NewsComment ? $comment : null;
    }

    /** @return list<NewsComment> */
    public function findForArticle(NewsArticle $article, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.article = :article')
            ->setParameter('article', $article)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(max(1, min(50, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getResult();
    }

    public function countForArticle(NewsArticle $article): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
