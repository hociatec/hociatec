<?php

declare(strict_types=1);

namespace App\Module\User\Service;

final class CommunicationPreferences
{
    public const NOTIFICATION = 'notification';
    public const EMAIL = 'email';
    public const PHONE = 'phone';

    /** @return list<string> */
    public static function allowed(): array
    {
        return [self::NOTIFICATION, self::EMAIL, self::PHONE];
    }

    /** @return list<string> */
    public static function normalize(mixed $value): array
    {
        $items = is_array($value) ? $value : [];

        return array_values(array_unique(array_filter(
            $items,
            static fn (mixed $item): bool => is_string($item) && in_array($item, self::allowed(), true),
        )));
    }

    /** @return list<array{value:string,label:string,description:string}> */
    public static function choices(): array
    {
        return [
            [
                'value' => self::NOTIFICATION,
                'label' => 'Notification dans mon compte',
                'description' => 'Affiche les informations importantes dans le menu de notifications Hociatec.',
            ],
            [
                'value' => self::EMAIL,
                'label' => 'E-mail',
                'description' => 'Envoie les informations importantes à l’adresse e-mail du compte.',
            ],
            [
                'value' => self::PHONE,
                'label' => 'Téléphone',
                'description' => 'Indique que vous acceptez un contact téléphonique quand un suivi le justifie.',
            ],
        ];
    }
}
