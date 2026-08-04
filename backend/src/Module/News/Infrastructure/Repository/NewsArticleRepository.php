<?php

declare(strict_types=1);

namespace App\Module\News\Infrastructure\Repository;

use App\Module\News\Domain\Entity\NewsArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NewsArticle> */
final class NewsArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsArticle::class);
    }

    /** @return list<NewsArticle> */
    public function findPublished(?string $search, int $limit, int $offset): array
    {
        $qb = $this->publishedQuery($search)
            ->orderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults(max(1, min(50, $limit)))
            ->setFirstResult(max(0, $offset));

        return $qb->getQuery()->getResult();
    }

    public function countPublished(?string $search): int
    {
        return (int) $this->publishedQuery($search)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<NewsArticle> */
    public function findForAdmin(?string $search, int $limit, int $offset): array
    {
        return $this->adminQuery($search)
            ->orderBy('a.updatedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?string $search): int
    {
        return (int) $this->adminQuery($search)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPublishedBySlug(string $slug): ?NewsArticle
    {
        $slug = trim($slug);
        $slug = trim($slug, " \t\n\r\0\x0B\"'«»“”.:,;!?");

        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.isPublished = :published')
            ->andWhere('a.publishedAt IS NULL OR a.publishedAt <= :now')
            ->setParameter('slug', $slug)
            ->setParameter('published', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function publishedQuery(?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = :published')
            ->andWhere('a.publishedAt IS NULL OR a.publishedAt <= :now')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTimeImmutable());

        $search = trim((string) $search);
        if ('' !== $search) {
            $qb->andWhere('LOWER(a.title) LIKE LOWER(:search) OR LOWER(a.excerpt) LIKE LOWER(:search) OR LOWER(a.content) LIKE LOWER(:search) OR LOWER(a.category) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb;
    }

    private function adminQuery(?string $search): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $search = trim((string) $search);
        if ('' !== $search) {
            $qb->andWhere('LOWER(a.title) LIKE LOWER(:search) OR LOWER(a.excerpt) LIKE LOWER(:search) OR LOWER(a.content) LIKE LOWER(:search) OR LOWER(a.category) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb;
    }
}
