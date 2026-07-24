<?php

declare(strict_types=1);

namespace App\Shared\Http;

final readonly class RoundcubeCredentialsProvider
{
    public function __construct(
        private string $ovhEmail,
        private string $ovhPassword,
        private string $mailerDsn,
    ) {
    }

    /** @return array{email:string, password:string}|null */
    public function provide(): ?array
    {
        if ('' !== $this->ovhEmail && '' !== $this->ovhPassword) {
            return ['email' => $this->ovhEmail, 'password' => $this->ovhPassword];
        }

        if ('' === $this->mailerDsn) {
            return null;
        }

        $parts = parse_url($this->mailerDsn);
        if (!is_array($parts)) {
            return null;
        }

        $email = isset($parts['user']) ? rawurldecode((string) $parts['user']) : '';
        $password = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : '';

        return '' !== $email && '' !== $password
            ? ['email' => $email, 'password' => $password]
            : null;
    }
}
