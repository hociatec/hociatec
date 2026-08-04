<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Marketing\Domain\Entity\EmailTemplate;

final readonly class EmailTemplateAdminManager
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function create(EmailTemplate $template): void
    {
        $this->persistence->persist($template);
        $this->persistence->flush();
    }

    public function save(EmailTemplate $template): void
    {
        $this->persistence->flush();
    }

    public function delete(EmailTemplate $template): void
    {
        $this->persistence->remove($template);
        $this->persistence->flush();
    }
}
