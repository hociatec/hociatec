<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\Handler;

use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteEmailTemplateHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function delete(EmailTemplate $template): void
    {
        $this->persistence->remove($template);
        $this->persistence->commit();
    }
}
