<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest\Entity;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class BetaTestEntitiesTest extends TestCase
{
    public function testBetaCampaignInstantiation(): void
    {
        $campaign = new BetaCampaign('Beta Campaign 1', 'A description of beta campaign');
        self::assertSame('Beta Campaign 1', $campaign->getName());
        self::assertSame('A description of beta campaign', $campaign->getDescription());
        self::assertSame('draft', $campaign->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getCreatedAt());
    }

    public function testBetaTesterProfileInstantiation(): void
    {
        $user = $this->createMock(User::class);
        $profile = new BetaTesterProfile([
            'user' => $user,
            'availability' => ['evening'],
            'motivation' => 'I want to help',
            'testingExperience' => 'None',
            'bugDescriptionAbility' => 'I can do it',
            'technicalKnowledge' => 'Some',
            'accessibilityNeed' => 'none',
            'assistiveTools' => [],
            'devices' => [],
            'browsers' => [],
            'testingTypes' => [],
            'consentAt' => new \DateTimeImmutable(),
            'privacyNoticeVersion' => '2026-07-26',
        ]);

        self::assertSame($user, $profile->getUser());
        self::assertSame('pending', $profile->getStatus());
        self::assertSame('I want to help', $profile->getMotivation());
    }

    public function testBugReportInstantiation(): void
    {
        $user = $this->createMock(User::class);
        $campaign = new BetaCampaign('Campaign', 'Desc');
        $bugReport = new BugReport([
            'reporter' => $user,
            'campaign' => $campaign,
            'title' => 'A bug',
            'description' => 'Bug description',
            'expectedBehavior' => 'Expected behavior',
            'actualBehavior' => 'Actual behavior',
            'severity' => 'high',
            'pageUrl' => 'https://example.com',
        ]);

        self::assertSame($user, $bugReport->getReporter());
        self::assertSame($campaign, $bugReport->getCampaign());
        self::assertSame('A bug', $bugReport->getTitle());
        self::assertSame('submitted', $bugReport->getStatus());
    }
}
