<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Marketing\Domain\Entity\EmailTemplate;

final readonly class UpdateEmailTemplateHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function update(EmailTemplate $template): void
    {
        $this->persistence->commit();
    }
}
