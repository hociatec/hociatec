<?php

declare(strict_types=1);

namespace App\Module\Training\Service;

use App\Module\Training\Entity\Training;
use App\Module\Training\Entity\TrainingRoadmapItem;

final class TrainingWriter
{
    /** @param array<string, mixed> $payload */
    public function apply(Training $training, array $payload): Training
    {
        $training
            ->setTitle(trim((string) ($payload['title'] ?? $training->getTitle())))
            ->setSlug($this->slugify((string) ($payload['slug'] ?? $payload['title'] ?? $training->getSlug())))
            ->setShortDescription($this->nullableString($payload['shortDescription'] ?? null))
            ->setObjective($this->nullableString($payload['objective'] ?? null))
            ->setAudience($this->nullableString($payload['audience'] ?? null))
            ->setCategory($this->category($payload['category'] ?? $training->getCategory()))
            ->setDurationMinutes(max(15, (int) ($payload['durationMinutes'] ?? $training->getDurationMinutes())))
            ->setPriceCents(max(0, (int) ($payload['priceCents'] ?? $training->getPriceCents())))
            ->setAvailableFormats($this->formats($payload['availableFormats'] ?? $training->getAvailableFormats()))
            ->setIsActive((bool) ($payload['isActive'] ?? $training->isActive()));

        $training->clearRoadmapItems();
        $position = 1;
        foreach ((array) ($payload['roadmap'] ?? []) as $item) {
            $title = trim((string) $item);
            if ('' === $title) {
                continue;
            }
            $training->addRoadmapItem(new TrainingRoadmapItem($position++, $title));
        }

        return $training;
    }

    public function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
        $value = trim($value, '-');

        return '' !== $value ? $value : 'formation';
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return '' !== $text ? $text : null;
    }

    private function category(mixed $value): string
    {
        $category = trim((string) $value);
        $category = preg_replace('/[^a-z0-9_-]+/', '-', mb_strtolower($category)) ?: '';
        $category = trim($category, '-_');

        return '' !== $category ? $category : 'general';
    }

    /** @return list<string> */
    private function formats(mixed $formats): array
    {
        if (!is_array($formats)) {
            return ['onsite'];
        }

        $allowed = ['onsite', 'remote'];
        $result = array_values(array_intersect($allowed, array_map('strval', $formats)));

        return [] !== $result ? $result : ['onsite'];
    }
}
