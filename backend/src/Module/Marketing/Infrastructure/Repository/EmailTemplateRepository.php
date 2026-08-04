<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\Repository;

use App\Module\Marketing\Domain\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
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
