<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Shared\Application\LockMode as ApplicationLockMode;
use App\Shared\Infrastructure\Doctrine\DoctrineLockModeMapper;
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
}
