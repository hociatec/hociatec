<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
use App\Shared\Infrastructure\Persistence\LikeSearchHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository implements EmailTemplateRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    public function find(mixed $id, ApplicationLockMode|LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?EmailTemplate
    {
        $template = parent::find($id, DoctrineLockModeMapper::toDoctrine($lockMode), $lockVersion);

        return $template instanceof EmailTemplate ? $template : null;
    }

    /** @return list<EmailTemplate> */
    public function findForAdmin(?string $search, ?string $scenario, ?string $status, int $limit, int $offset): array
    {
        return $this->createAdminQuery($search, $scenario, $status)
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults(max(1, min(50, $limit)))
            ->setFirstResult(max(0, $offset))
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?string $search, ?string $scenario, ?string $status): int
    {
        return (int) $this->createAdminQuery($search, $scenario, $status)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneBySlug(string $slug): ?EmailTemplate
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findActiveOneByScenarioKey(string $scenarioKey): ?EmailTemplate
    {
        return $this->findOneBy([
            'scenarioKey' => $scenarioKey,
            'isActive' => true,
        ], [
            'updatedAt' => 'DESC',
        ]);
    }

    private function createAdminQuery(?string $search, ?string $scenario, ?string $status): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');
        $searchPattern = LikeSearchHelper::containsPattern($search, true);

        if (null !== $searchPattern) {
            $qb
                ->andWhere('LOWER(t.name) LIKE :search OR LOWER(t.slug) LIKE :search OR LOWER(t.subjectTemplate) LIKE :search')
                ->setParameter('search', $searchPattern);
        }

        if (null !== $scenario && '' !== $scenario) {
            $qb->andWhere('t.scenarioKey = :scenario')->setParameter('scenario', $scenario);
        }

        if ('active' === $status) {
            $qb->andWhere('t.isActive = :active')->setParameter('active', true);
        } elseif ('inactive' === $status) {
            $qb->andWhere('t.isActive = :active')->setParameter('active', false);
        }

        return $qb;
    }
}
