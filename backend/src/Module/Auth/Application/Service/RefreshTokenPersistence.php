<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\Service;

use App\Module\Auth\Domain\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RefreshTokenPersistence
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(RefreshToken $token): void
    {
        $this->entityManager->persist($token);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
