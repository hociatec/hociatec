<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Mapper;

final class BetaProfileChoices
{
    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function groups(): array
    {
        return [
            'availability' => [
                ['value' => 'weekdays', 'label' => 'En semaine'],
                ['value' => 'evenings', 'label' => 'En soirée'],
                ['value' => 'weekends', 'label' => 'Le week-end'],
                ['value' => 'flexible', 'label' => 'Flexible'],
            ],
            'testingExperience' => [
                ['value' => 'none', 'label' => 'Aucune expérience de test'],
                ['value' => 'occasional', 'label' => 'Tests occasionnels'],
                ['value' => 'regular', 'label' => 'Tests réguliers'],
                ['value' => 'professional', 'label' => 'Expérience professionnelle'],
            ],
            'bugDescriptionAbility' => [
                ['value' => 'basic', 'label' => 'Je peux expliquer le problème avec mes mots'],
                ['value' => 'steps', 'label' => 'Je peux lister les actions faites avant le problème'],
                ['value' => 'expected_actual', 'label' => 'Je peux comparer ce qui était attendu avec ce qui s’est passé'],
                ['value' => 'screenshots', 'label' => 'Je peux ajouter une capture d’écran ou une pièce jointe'],
                ['value' => 'environment', 'label' => 'Je peux préciser l’appareil, le navigateur et la page concernés'],
                ['value' => 'frequency', 'label' => 'Je peux dire si le problème arrive une fois ou à chaque essai'],
            ],
            'technicalKnowledge' => [
                ['value' => 'none', 'label' => 'Aucune connaissance technique particulière'],
                ['value' => 'basic', 'label' => 'Utilisation courante d’un ordinateur ou smartphone'],
                ['value' => 'web', 'label' => 'Compréhension des sites web et navigateurs'],
                ['value' => 'troubleshooting', 'label' => 'Diagnostic simple : vider le cache, tester un autre navigateur, redémarrer'],
                ['value' => 'accessibility', 'label' => 'Usage ou test avec outils d’accessibilité'],
            ],
            'assistiveTools' => [
                ['value' => 'none', 'label' => 'Aucun outil d’assistance'],
                ['value' => 'nvda', 'label' => 'NVDA'],
                ['value' => 'jaws', 'label' => 'JAWS'],
                ['value' => 'voiceover', 'label' => 'VoiceOver'],
                ['value' => 'talkback', 'label' => 'TalkBack'],
                ['value' => 'narrator', 'label' => 'Narrator'],
                ['value' => 'magnifier', 'label' => 'Loupe'],
                ['value' => 'keyboard', 'label' => 'Navigation au clavier'],
                ['value' => 'braille', 'label' => 'Plage braille'],
                ['value' => 'other', 'label' => 'Autre'],
            ],
            'devices' => [
                ['value' => 'windows', 'label' => 'Windows'],
                ['value' => 'macos', 'label' => 'macOS'],
                ['value' => 'linux', 'label' => 'Linux'],
                ['value' => 'android', 'label' => 'Android'],
                ['value' => 'ios', 'label' => 'iPhone/iPad'],
            ],
            'browsers' => [
                ['value' => 'chrome', 'label' => 'Chrome'],
                ['value' => 'firefox', 'label' => 'Firefox'],
                ['value' => 'edge', 'label' => 'Edge'],
                ['value' => 'safari', 'label' => 'Safari'],
                ['value' => 'other', 'label' => 'Autre'],
            ],
            'testingTypes' => [
                ['value' => 'bugs', 'label' => 'Anomalies'],
                ['value' => 'accessibility', 'label' => 'Accessibilité'],
                ['value' => 'usability', 'label' => 'Ergonomie'],
                ['value' => 'mobile', 'label' => 'Mobile'],
                ['value' => 'performance', 'label' => 'Performances'],
                ['value' => 'features', 'label' => 'Nouvelles fonctionnalités'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(string $group): array
    {
        return array_map(
            static fn (array $choice): string => $choice['value'],
            self::groups()[$group] ?? [],
        );
    }

    /**
     * @return list<string>
     */
    public static function normalizeList(mixed $value, string $group): array
    {
        $values = is_array($value) ? $value : (is_string($value) && '' !== trim($value) ? [$value] : []);
        $allowed = self::values($group);

        $normalized = array_values(array_unique(array_filter(
            $values,
            static fn (mixed $item): bool => is_string($item) && in_array($item, $allowed, true),
        )));

        if ('assistiveTools' === $group && count($normalized) > 1 && in_array('none', $normalized, true)) {
            return array_values(array_filter(
                $normalized,
                static fn (string $item): bool => 'none' !== $item,
            ));
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public static function parseStoredList(?string $value, string $group): array
    {
        if (null === $value || '' === trim($value)) {
            return [];
        }

        return self::normalizeList(explode(',', $value), $group);
    }

    /**
     * @param list<string> $values
     */
    public static function serializeList(array $values): string
    {
        return implode(',', $values);
    }
}
