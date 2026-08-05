<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Notification;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;

final class EmailTemplateRenderer
{
    public function __construct(private readonly EmailTemplateRepositoryPort $templates)
    {
    }

    /**
     * @param array<string, string>                              $context
     * @param array{subject: string, html: string, text: string} $fallback
     *
     * @return array{subject: string, html: string, text: string}
     */
    public function renderScenario(string $scenarioKey, array $context, array $fallback): array
    {
        $template = $this->templates->findActiveOneByScenarioKey($scenarioKey);

        return [
            'subject' => $this->render($template?->getSubjectTemplate() ?? $fallback['subject'], $context, false),
            'html' => $this->render($template?->getHtmlBody() ?? $fallback['html'], $context, true),
            'text' => $this->render($template?->getTextBody() ?? $fallback['text'], $context, false),
        ];
    }

    /**
     * @param array<string, string> $context
     */
    private function render(?string $template, array $context, bool $allowHtml): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = $allowHtml
                ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $value;
        }

        return strtr((string) $template, $replacements);
    }
}
