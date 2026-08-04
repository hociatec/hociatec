<?php

declare(strict_types=1);

namespace App\Module\Training\Application\Service;

use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;

final class TrainingMetadataFormatter
{
    /** @var array<string, array{id: int|null, name: string, slug: string}|null>|null */
    private ?array $categories = null;

    public function __construct(private readonly TrainingCategoryRepository $categoryRepository)
    {
    }

    /** @return array{id: int|null, name: string, slug: string}|null */
    public function category(string $slug): ?array
    {
        $this->loadCategories();

        return $this->categories[$slug] ?? null;
    }

    /**
     * @param array<mixed> $formats
     *
     * @return list<array{value: string, label: string}>
     */
    public function formats(array $formats): array
    {
        $labels = [
            'onsite' => 'Présentiel',
            'remote' => 'Distanciel',
        ];

        $options = [];
        foreach ($formats as $format) {
            if (!is_string($format) || '' === trim($format)) {
                continue;
            }

            $options[] = [
                'value' => $format,
                'label' => $labels[$format] ?? $format,
            ];
        }

        return $options;
    }

    public function formatLabel(string $format): string
    {
        return match ($format) {
            'onsite' => 'Présentiel',
            'remote' => 'Distanciel',
            default => $format,
        };
    }

    public function enrollmentStatusLabel(string $status): string
    {
        return [
            'pending_payment' => 'Paiement en attente',
            'paid' => 'Payée',
            'confirmed' => 'Confirmée',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
        ][$status] ?? $status;
    }

    private function loadCategories(): void
    {
        if (null !== $this->categories) {
            return;
        }

        $this->categories = [];
        foreach ($this->categoryRepository->findOrdered() as $category) {
            $this->categories[$category->getSlug()] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ];
        }
    }
}
