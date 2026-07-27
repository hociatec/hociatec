<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Service;

use App\Module\BetaTest\DTO\BetaProfileInput;
use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\BetaTest\Repository\BetaTesterProfileRepository;
use App\Module\User\Entity\User;
use App\Shared\Persistence\DoctrinePersistence;

final readonly class BetaTesterProfileService
{
    public function __construct(private BetaTesterProfileRepository $profiles, private DoctrinePersistence $persistence) {}

    public function create(User $user, BetaProfileInput $input): BetaTesterProfile
    {
        $profile = new BetaTesterProfile($user, $input->availability, $input->motivation, $input->testingExperience, $input->bugDescriptionAbility, $input->technicalKnowledge, $input->accessibilityNeed, $input->assistiveTools, $input->devices, $input->browsers, $input->testingTypes, new \DateTimeImmutable(), '2026-07-26');
        $this->persistence->persist($profile);
        return $profile;
    }
}
