<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Service;

use App\Module\Marketing\Entity\EmailTemplate;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EmailTemplateAdminManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function create(EmailTemplate $template): void
    {
        $this->entityManager->persist($template);
        $this->entityManager->flush();
    }

    public function save(EmailTemplate $template): void
    {
        $this->entityManager->flush();
    }

    public function delete(EmailTemplate $template): void
    {
        $this->entityManager->remove($template);
        $this->entityManager->flush();
    }
}
