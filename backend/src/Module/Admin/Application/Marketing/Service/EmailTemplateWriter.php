<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Marketing\Service;

use App\Module\Admin\Application\Marketing\DTO\MarketingTemplateInput;
use App\Module\Marketing\Application\Provider\EmailTemplateScenarioProvider;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;

final readonly class EmailTemplateWriter
{
    public function __construct(
        private CreateEmailTemplateHandler $createTemplate,
        private UpdateEmailTemplateHandler $updateTemplate,
        private EmailTemplateRepository $templates,
        private EmailTemplateScenarioProvider $scenarioProvider,
    ) {
    }

    public function create(MarketingTemplateInput $input): EmailTemplate
    {
        $this->assertScenarioExists($input->scenarioKey);
        if (null !== $this->templates->findOneBySlug($input->slug)) {
            throw new \InvalidArgumentException('Ce slug de template est déjà utilisé.');
        }

        $template = new EmailTemplate($input->name, $input->slug, $input->scenarioKey, $input->subjectTemplate, $input->htmlBody, $input->textBody);
        $template->setIsActive($input->isActive);

        $this->createTemplate->create($template);

        return $template;
    }

    public function update(EmailTemplate $template, MarketingTemplateInput $input): EmailTemplate
    {
        $this->assertScenarioExists($input->scenarioKey);
        $existing = $this->templates->findOneBySlug($input->slug);
        if (null !== $existing && $existing->getId() !== $template->getId()) {
            throw new \InvalidArgumentException('Ce slug de template est déjà utilisé.');
        }

        $template
            ->setName($input->name)
            ->setSlug($input->slug)
            ->setScenarioKey($input->scenarioKey)
            ->setSubjectTemplate($input->subjectTemplate)
            ->setHtmlBody($input->htmlBody)
            ->setTextBody($input->textBody)
            ->setIsActive($input->isActive);

        $this->updateTemplate->update($template);

        return $template;
    }

    private function assertScenarioExists(string $scenarioKey): void
    {
        if (!isset($this->scenarioProvider->getTemplateScenarioDefinitions()[$scenarioKey])) {
            throw new \InvalidArgumentException('Scénario de template invalide.');
        }
    }
}
