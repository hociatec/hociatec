<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\Service;

use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class CreateEmailTemplateHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function create(EmailTemplate $template): void
    {
        $this->persistence->persist($template);
        $this->persistence->commit();
    }
}
