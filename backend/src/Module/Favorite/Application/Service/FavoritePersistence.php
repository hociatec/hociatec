<?php

declare(strict_types=1);

namespace App\Module\Favorite\Application\Service;

use App\Module\Favorite\Domain\Entity\Favorite;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FavoritePersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Favorite $favorite): void
    {
        $this->entityManager->persist($favorite);
    }

    public function delete(Favorite $favorite): void
    {
        $this->entityManager->remove($favorite);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
