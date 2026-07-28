<?php

declare(strict_types=1);

namespace App\Module\News\Repository;

use App\Module\News\Entity\NewsArticle;
use App\Module\News\Entity\NewsArticleView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NewsArticleView> */
final class NewsArticleViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsArticleView::class);
    }

    public function findOneForArticleAndIpHash(NewsArticle $article, string $ipHash): ?NewsArticleView
    {
        return $this->findOneBy(['article' => $article, 'ipHash' => $ipHash]);
    }

    public function countUniqueForArticle(NewsArticle $article): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.article = :article')
            ->setParameter('article', $article)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
