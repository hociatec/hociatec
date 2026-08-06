<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Provider;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Application\Port\QuoteRepositoryPort;

final class OrderNotificationContentProvider
{
    private readonly OrderNotificationContextBuilder $contextBuilder;

    public function __construct(
        private readonly EmailTemplateRepositoryPort $templates,
        QuoteRepositoryPort $quotes,
        string $frontendUrl,
        ?OrderNotificationContextBuilder $contextBuilder = null,
        private readonly OrderNotificationTemplateRenderer $renderer = new OrderNotificationTemplateRenderer(),
    ) {
        $this->contextBuilder = $contextBuilder ?? new OrderNotificationContextBuilder($quotes, $frontendUrl);
    }

    /**
     * @param array<string, string> $extraContext
     *
     * @return array<string, string>
     */
    public function build(Order $order, string $scenarioKey, array $extraContext = []): array
    {
        $template = $this->templates->findActiveOneByScenarioKey($scenarioKey);
        $fallback = OrderNotificationFallbackTemplates::forScenario($scenarioKey);
        $context = $this->contextBuilder->build($order, $extraContext);

        return [
            'subject' => $this->renderer->render($template?->getSubjectTemplate() ?? $fallback['subject'], $context, false),
            'html' => $this->renderer->render($template?->getHtmlBody() ?? $fallback['html'], $context, true),
            'text' => $this->renderer->render($template?->getTextBody() ?? $fallback['text'], $context, false),
        ];
    }
}
