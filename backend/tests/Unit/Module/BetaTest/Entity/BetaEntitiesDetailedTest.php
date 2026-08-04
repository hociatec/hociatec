<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\BetaTest\Entity;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class BetaEntitiesDetailedTest extends TestCase
{
    public function testBetaCampaignMutatorsAndOpenState(): void
    {
        $campaign = new BetaCampaign('Campaign', 'Desc', new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('+1 day'));

        self::assertNull($campaign->getId());
        $campaign
            ->setName('Campaign 2')
            ->setDescription('Desc 2')
            ->setStartsAt(new \DateTimeImmutable('-2 day'))
            ->setEndsAt(new \DateTimeImmutable('+2 day'))
            ->setStatus(BetaCampaign::STATUS_ACTIVE);

        self::assertSame('Campaign 2', $campaign->getName());
        self::assertSame('Desc 2', $campaign->getDescription());
        self::assertSame(BetaCampaign::STATUS_ACTIVE, $campaign->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getStartsAt());
        self::assertSame(BetaCampaign::STATUS_ACTIVE, $campaign->getEffectiveStatus(new \DateTimeImmutable('now')));
        self::assertTrue($campaign->isOpenForReports(new \DateTimeImmutable('now')));

        $campaign->setEndsAt(new \DateTimeImmutable('-1 hour'));
        self::assertSame(BetaCampaign::STATUS_CLOSED, $campaign->getEffectiveStatus(new \DateTimeImmutable('now')));
        self::assertFalse($campaign->isOpenForReports(new \DateTimeImmutable('now')));
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getStartsAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getEndsAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getCreatedAt());
    }

    public function testBetaTesterProfileUpdateAndStatus(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $consentAt = new \DateTimeImmutable('2026-07-01T10:00:00+00:00');

        $profile = new BetaTesterProfile(
            $user,
            ['weekdays'],
            'Motivation',
            'regular',
            'steps',
            'web',
            'none',
            ['nvda'],
            ['windows'],
            ['chrome'],
            ['bugs'],
            $consentAt,
            'v1'
        );

        self::assertNull($profile->getId());
        $updatedAt = $profile->getUpdatedAt();
        $profile
            ->setStatus(BetaTesterProfile::STATUS_ACCEPTED)
            ->update(
                ['weekends'],
                'Updated motivation',
                'professional',
                'expected_actual',
                'troubleshooting',
                'other',
                ['keyboard'],
                ['macos'],
                ['safari'],
                ['accessibility'],
            );

        self::assertSame($user, $profile->getUser());
        self::assertSame(BetaTesterProfile::STATUS_ACCEPTED, $profile->getStatus());
        self::assertSame(['weekends'], $profile->getAvailability());
        self::assertSame('Updated motivation', $profile->getMotivation());
        self::assertSame('professional', $profile->getTestingExperience());
        self::assertSame('expected_actual', $profile->getBugDescriptionAbility());
        self::assertSame('troubleshooting', $profile->getTechnicalKnowledge());
        self::assertSame('other', $profile->getAccessibilityNeed());
        self::assertSame(['keyboard'], $profile->getAssistiveTools());
        self::assertSame(['macos'], $profile->getDevices());
        self::assertSame(['safari'], $profile->getBrowsers());
        self::assertSame(['accessibility'], $profile->getTestingTypes());
        self::assertSame($consentAt, $profile->getConsentAt());
        self::assertSame($consentAt, $profile->getCreatedAt());
        self::assertGreaterThanOrEqual($updatedAt, $profile->getUpdatedAt());
    }

    public function testBugReportMutatorsAssignmentDuplicationAndReplies(): void
    {
        $reporter = new User('user@example.com', 'User', 'One', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $admin = new User('admin@example.com', 'Admin', 'Two', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $admin->setRoles(['ROLE_ADMIN']);

        $campaign = new BetaCampaign('Campaign', 'Desc');
        $bug = new BugReport($reporter, $campaign, 'Title', 'Desc', 'Expected', 'Actual', 'high', 'https://example.com', ['a.png']);
        $duplicate = new BugReport($reporter, null, 'Dup', 'Desc', null, null, 'low', null);

        self::assertNull($bug->getId());
        $updatedAt = $bug->getUpdatedAt();
        $bug->setStatus(BugReport::STATUS_UNDER_REVIEW);
        self::assertSame(BugReport::STATUS_UNDER_REVIEW, $bug->getStatus());
        self::assertGreaterThanOrEqual($updatedAt, $bug->getUpdatedAt());

        $bug->assignTo($admin);
        self::assertSame($admin, $bug->getAssignedTo());
        self::assertInstanceOf(\DateTimeImmutable::class, $bug->getAssignedAt());

        $bug->markDuplicateOf($duplicate, ' duplicate ');
        self::assertSame($duplicate, $bug->getDuplicateOf());
        self::assertSame('duplicate', $bug->getDuplicateReason());
        self::assertSame(BugReport::STATUS_DUPLICATE, $bug->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $bug->getDuplicatedAt());

        $bug->assignTo(null)->markDuplicateOf(null, ' ');
        self::assertNull($bug->getAssignedTo());
        self::assertNull($bug->getAssignedAt());
        self::assertNull($bug->getDuplicateOf());
        self::assertNull($bug->getDuplicateReason());
        self::assertNull($bug->getDuplicatedAt());

        $bug->recordAdminReply();
        self::assertInstanceOf(\DateTimeImmutable::class, $bug->getLastAdminReplyAt());
        $bug->recordReporterReply();
        self::assertInstanceOf(\DateTimeImmutable::class, $bug->getLastReporterReplyAt());

        self::assertSame($reporter, $bug->getReporter());
        self::assertSame($campaign, $bug->getCampaign());
        self::assertSame('Title', $bug->getTitle());
        self::assertSame('Desc', $bug->getDescription());
        self::assertSame('Expected', $bug->getExpectedBehavior());
        self::assertSame('Actual', $bug->getActualBehavior());
        self::assertSame('high', $bug->getSeverity());
        self::assertSame('https://example.com', $bug->getPageUrl());
        self::assertSame(['a.png'], $bug->getAttachments());
        self::assertInstanceOf(\DateTimeImmutable::class, $bug->getCreatedAt());
    }
}
