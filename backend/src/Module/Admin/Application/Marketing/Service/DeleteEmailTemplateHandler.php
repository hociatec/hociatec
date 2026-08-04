<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Marketing\Domain\Entity\EmailTemplate;

final readonly class DeleteEmailTemplateHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function delete(EmailTemplate $template): void
    {
        $this->persistence->remove($template);
        $this->persistence->commit();
    }
}
