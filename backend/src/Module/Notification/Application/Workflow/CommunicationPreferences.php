<?php

declare(strict_types=1);

namespace App\Module\Notification\Application\Workflow;

final class CommunicationPreferences
{
    public const NOTIFICATION = 'notification';
    public const EMAIL = 'email';
    public const NEWS_EMAIL = 'news_email';
    public const PHONE = 'phone';

    /** @return list<string> */
    public static function allowed(): array
    {
        return [self::NOTIFICATION, self::EMAIL, self::NEWS_EMAIL, self::PHONE];
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
                'value' => self::NEWS_EMAIL,
                'label' => 'Actualités par e-mail',
                'description' => 'Autorise Hociatec à vous envoyer les actualités, annonces et informations éditoriales par e-mail.',
            ],
            [
                'value' => self::PHONE,
                'label' => 'Téléphone',
                'description' => 'Indique que vous acceptez un contact téléphonique quand un suivi le justifie.',
            ],
        ];
    }
}
