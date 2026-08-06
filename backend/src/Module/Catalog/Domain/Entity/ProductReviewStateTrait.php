<?php

declare(strict_types=1);

namespace App\Module\Catalog\Domain\Entity;

trait ProductReviewStateTrait
{
    public function getReviewsCount(): int
    {
        return $this->reviewsCount;
    }

    public function setReviewsCount(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Le nombre d’avis ne peut pas être négatif.');
        }

        $this->reviewsCount = $count;

        return $this;
    }

    public function getReviewsAverage(): float
    {
        return $this->reviewsAverage;
    }

    public function setReviewsAverage(float $average): self
    {
        if ($average < 0 || $average > 5) {
            throw new \InvalidArgumentException('La moyenne des avis doit etre comprise entre 0 et 5.');
        }

        $this->reviewsAverage = $average;

        return $this;
    }
}
