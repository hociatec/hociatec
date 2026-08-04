<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateAuditRequestDto
{
    #[Assert\NotBlank(message: "Le type d'audit est requis.")]
    public string $type = '';

    #[Assert\NotBlank(message: 'URL ou accès requis.')]
    #[Assert\Length(max: 255, maxMessage: 'URL trop longue.')]
    public string $url = '';

    #[Assert\Length(max: 5000, maxMessage: 'Objectifs trop longs.')]
    public ?string $objectives = null;
}
