<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Projection;

use App\Module\Auth\Domain\Entity\RefreshToken;

final readonly class RefreshTokenSessionFormatter
{
    /**
     * @return array{
     *   id:int|null,
     *   deviceLabel:string,
     *   platformLabel:string,
     *   clientLabel:string,
     *   locationLabel:string,
     *   createdAt:string,
     *   lastUsedAt:string,
     *   expiresAt:string,
     *   current:bool
     * }
     */
    public function format(RefreshToken $token, ?string $currentSelector): array
    {
        return [
            'id' => $token->getId(),
            'deviceLabel' => $token->getDeviceLabel() ?? 'Appareil inconnu',
            'platformLabel' => $token->getPlatformLabel() ?? 'Système inconnu',
            'clientLabel' => $token->getClientLabel() ?? 'Client inconnu',
            'locationLabel' => $token->getLocationLabel() ?? 'Localisation indisponible',
            'createdAt' => $token->getCreatedAt()->format(DATE_ATOM),
            'lastUsedAt' => ($token->getLastUsedAt() ?? $token->getCreatedAt())->format(DATE_ATOM),
            'expiresAt' => $token->getExpiresAt()->format(DATE_ATOM),
            'current' => $currentSelector === $token->getSelector(),
        ];
    }
}
