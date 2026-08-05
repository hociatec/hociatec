<?php

declare(strict_types=1);

namespace App\Module\Marketing\UI\Http;

use App\Module\Marketing\Domain\Entity\EmailTemplate;

final readonly class EmailTemplateResponseFormatter
{
    /** @return array<string, mixed> */
    public function format(EmailTemplate $template): array
    {
        return [
            'id' => $template->getId(),
            'name' => $template->getName(),
            'slug' => $template->getSlug(),
            'scenarioKey' => $template->getScenarioKey(),
            'subjectTemplate' => $template->getSubjectTemplate(),
            'htmlBody' => $template->getHtmlBody(),
            'textBody' => $template->getTextBody(),
            'isActive' => $template->isActive(),
            'createdAt' => $template->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $template->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
