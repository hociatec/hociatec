<?php

declare(strict_types=1);

namespace App\Module\Training\Service;

use App\Module\Training\DTO\TrainingInput;
use App\Module\Training\Entity\Training;
use App\Module\Training\Entity\TrainingRoadmapItem;
use App\Shared\Persistence\DoctrinePersistence;

final class TrainingWriter
{
    public function __construct(private readonly DoctrinePersistence $persistence)
    {
    }

    public function save(object $entity): void
    {
        $this->persistence->persist($entity);
        $this->persistence->flush();
    }

    public function delete(object $entity): void
    {
        $this->persistence->remove($entity);
        $this->persistence->flush();
    }

    public function apply(Training $training, TrainingInput $input): Training
    {
        $training
            ->setTitle($input->title)
            ->setSlug($this->slugify($input->slug ?? $input->title))
            ->setShortDescription($this->nullableString($input->shortDescription))
            ->setObjective($this->nullableString($input->objective))
            ->setAudience($this->nullableString($input->audience))
            ->setCategory($this->category($input->category ?? $training->getCategory()))
            ->setDurationMinutes(max(15, $input->durationMinutes))
            ->setPriceCents($input->priceCents)
            ->setAvailableFormats($this->formats($input->availableFormats))
            ->setIsActive($input->isActive);

        $training->clearRoadmapItems();
        $position = 1;
        foreach ($input->roadmap as $item) {
            $title = trim($item);
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
