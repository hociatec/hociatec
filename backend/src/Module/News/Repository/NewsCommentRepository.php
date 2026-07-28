<?php

declare(strict_types=1);

namespace App\Module\News\Repository;

use App\Module\News\Entity\NewsArticle;
use App\Module\News\Entity\NewsComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NewsComment> */
final class NewsCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsComment::class);
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
