<?php

declare(strict_types=1);

namespace App\Module\Admin\Marketing\Service;

use App\Module\Marketing\Entity\EmailTemplate;
use App\Shared\Persistence\DoctrinePersistence;

final readonly class EmailTemplateAdminManager
{
    public function __construct(private DoctrinePersistence $persistence)
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
