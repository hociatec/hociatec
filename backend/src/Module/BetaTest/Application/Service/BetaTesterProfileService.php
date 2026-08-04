<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Application\DTO\BetaProfileInput;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\User\Domain\Entity\User;

final readonly class BetaTesterProfileService
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function create(User $user, BetaProfileInput $input): BetaTesterProfile
    {
        $profile = new BetaTesterProfile($user, $input->availability, $input->motivation, $input->testingExperience, $input->bugDescriptionAbility, $input->technicalKnowledge, $input->accessibilityNeed, $input->assistiveTools, $input->devices, $input->browsers, $input->testingTypes, new \DateTimeImmutable(), '2026-07-26');
        $this->persistence->persist($profile);

        return $profile;
    }

    public function save(User $user, ?BetaTesterProfile $profile, BetaProfileInput $input): BetaTesterProfile
    {
        if (null === $profile) {
            $profile = $this->create($user, $input);
        } else {
            $profile->update(
                $input->availability,
                $input->motivation,
                $input->testingExperience,
                $input->bugDescriptionAbility,
                $input->technicalKnowledge,
                $input->accessibilityNeed,
                $input->assistiveTools,
                $input->devices,
                $input->browsers,
                $input->testingTypes
            );
        }

        $this->persistence->flush();

        return $profile;
    }

    public function delete(?BetaTesterProfile $profile): void
    {
        if (null === $profile) {
            return;
        }

        $this->persistence->remove($profile);
        $this->persistence->flush();
    }
}
