<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest\Entity;

use App\Module\BetaTest\Entity\BetaCampaign;
use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Module\BetaTest\Entity\BugReport;
use App\Module\User\Entity\User;
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
        $profile = new BetaTesterProfile(
            $user,
            ['evening'],
            'I want to help',
            'None',
            'I can do it',
            'Some',
            'none',
            [],
            [],
            [],
            [],
            new \DateTimeImmutable(),
            '2026-07-26'
        );

        self::assertSame($user, $profile->getUser());
        self::assertSame('pending', $profile->getStatus());
        self::assertSame('I want to help', $profile->getMotivation());
    }

    public function testBugReportInstantiation(): void
    {
        $user = $this->createMock(User::class);
        $campaign = new BetaCampaign('Campaign', 'Desc');
        $bugReport = new BugReport(
            $user,
            $campaign,
            'A bug',
            'Bug description',
            'Expected behavior',
            'Actual behavior',
            'high',
            'https://example.com'
        );

        self::assertSame($user, $bugReport->getReporter());
        self::assertSame($campaign, $bugReport->getCampaign());
        self::assertSame('A bug', $bugReport->getTitle());
        self::assertSame('submitted', $bugReport->getStatus());
    }
}
