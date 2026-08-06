<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Provider;

final readonly class EmailTemplateScenarioProvider
{
    public function __construct(
        private CampaignEmailScenarioProvider $campaigns = new CampaignEmailScenarioProvider(),
        private TransactionalEmailScenarioProvider $transactional = new TransactionalEmailScenarioProvider(),
    ) {
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function getCampaignScenarioDefinitions(): array
    {
        return $this->campaigns->definitions();
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function getTransactionalTemplateScenarioDefinitions(): array
    {
        return $this->transactional->definitions();
    }

    /**
     * @return array<string, array{label: string, description: string, defaults: array<string, int|string|bool>, type: string}>
     */
    public function getTemplateScenarioDefinitions(): array
    {
        return $this->getCampaignScenarioDefinitions() + $this->getTransactionalTemplateScenarioDefinitions();
    }
}
