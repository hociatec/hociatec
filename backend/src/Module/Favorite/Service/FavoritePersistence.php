<?php

declare(strict_types=1);

namespace App\Module\Favorite\Service;

use App\Module\Favorite\Entity\Favorite;
use Doctrine\ORM\EntityManagerInterface;

final readonly class FavoritePersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Favorite $favorite): void
    {
        $this->entityManager->persist($favorite);
        $this->entityManager->flush();
    }

    public function delete(Favorite $favorite): void
    {
        $this->entityManager->remove($favorite);
        $this->entityManager->flush();
    }
}
