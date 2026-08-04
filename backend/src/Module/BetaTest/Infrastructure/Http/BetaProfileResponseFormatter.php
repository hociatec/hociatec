<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Infrastructure\Http;

use App\Module\BetaTest\Application\Mapper\BetaProfileChoices;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;

final readonly class BetaProfileResponseFormatter
{
    /** @return array<string, mixed> */
    public function format(BetaTesterProfile $profile): array
    {
        return [
            'id' => $profile->getId(),
            'status' => $profile->getStatus(),
            'availability' => $profile->getAvailability(),
            'motivation' => $profile->getMotivation(),
            'testingExperience' => BetaProfileChoices::parseStoredList($profile->getTestingExperience(), 'testingExperience'),
            'bugDescriptionAbility' => BetaProfileChoices::parseStoredList($profile->getBugDescriptionAbility(), 'bugDescriptionAbility'),
            'technicalKnowledge' => BetaProfileChoices::parseStoredList($profile->getTechnicalKnowledge(), 'technicalKnowledge'),
            'accessibilityNeed' => $profile->getAccessibilityNeed(),
            'assistiveTools' => $profile->getAssistiveTools(),
            'devices' => $profile->getDevices(),
            'browsers' => $profile->getBrowsers(),
            'testingTypes' => $profile->getTestingTypes(),
        ];
    }
}
