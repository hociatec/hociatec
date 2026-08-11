<?php

declare(strict_types=1);

namespace App\Module\Outbox\Infrastructure\Alert;

use App\Module\Outbox\Application\OutboxAlert;
use App\Module\Outbox\Application\OutboxAlertNotifier;
use App\Shared\Infrastructure\Http\OutboundUrlGuard;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class WebhookOutboxAlertNotifier implements OutboxAlertNotifier
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $webhookUrl = '',
    ) {
    }

    public function notify(OutboxAlert $alert): void
    {
        if ('' === trim($this->webhookUrl)) {
            $this->logger->warning('Outbox alert raised without configured webhook.', $alert->toArray());

            return;
        }

        try {
            OutboundUrlGuard::assertAllowedHttpUrl($this->webhookUrl);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('Outbox alert webhook rejected by URL policy.', [
                'alert' => $alert->toArray(),
                'exception' => $exception,
            ]);

            return;
        }

        try {
            $this->httpClient->request('POST', $this->webhookUrl, [
                'json' => $alert->toArray(),
                'timeout' => 5,
            ])->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Outbox alert webhook failed.', [
                'alert' => $alert->toArray(),
                'exception' => $exception,
            ]);
        }
    }
}
