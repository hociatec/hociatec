<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Security;

final readonly class CheckoutRedirectUrlValidator
{
    /** @var array<string, true> */
    private array $trustedHosts;

    public function __construct(string $frontendUrl)
    {
        $frontendHost = parse_url($frontendUrl, PHP_URL_HOST);
        $trustedHosts = ['checkout.stripe.com' => true];

        if (is_string($frontendHost) && '' !== $frontendHost) {
            $trustedHosts[strtolower($frontendHost)] = true;
        }

        $this->trustedHosts = $trustedHosts;
    }

    public function assertTrusted(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower(is_string($parts['scheme'] ?? null) ? $parts['scheme'] : '');
        $host = strtolower(is_string($parts['host'] ?? null) ? $parts['host'] : '');

        if ('https' !== $scheme || !isset($this->trustedHosts[$host])) {
            throw new \RuntimeException('URL de redirection de paiement non autorisée.');
        }
    }
}
