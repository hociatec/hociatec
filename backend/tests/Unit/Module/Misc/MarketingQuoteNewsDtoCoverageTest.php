<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Marketing\DTO\MarketingAudienceInput;
use App\Module\Admin\Application\Marketing\DTO\MarketingCampaignInput;
use App\Module\Admin\Application\Marketing\DTO\MarketingTemplateInput;
use App\Module\Admin\Application\Quote\DTO\QuoteEmailInput;
use App\Module\Admin\Application\Quote\DTO\QuotePayloadInput;
use App\Module\Admin\Application\Quote\DTO\QuoteProductItemInput;
use App\Module\Admin\Application\Quote\DTO\QuoteServiceFormData;
use App\Module\Admin\Application\Quote\DTO\QuoteStatusInput;
use App\Module\Audit\Application\DTO\CreateAuditRequestDto;
use App\Module\News\Application\DTO\CreateNewsCommentInput;
use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\Quote\Domain\Entity\Quote;
use App\Shared\Domain\ValueObject\EmailAddress;
use PHPUnit\Framework\TestCase;

final class MarketingQuoteNewsDtoCoverageTest extends TestCase
{
    public function testMarketingDtos(): void
    {
        $audience = MarketingAudienceInput::fromArray(['segmentKey' => ' vip ', 'criteria' => ['country' => 'FR']]);
        self::assertSame('vip', $audience->segmentKey);
        self::assertSame(['country' => 'FR'], $audience->criteria);

        $campaign = MarketingCampaignInput::fromArray([
            'name' => ' Summer ',
            'segmentKey' => ' loyal ',
            'criteria' => ['minOrders' => 3],
            'subject' => ' Subject ',
            'htmlBody' => ' <b>Hi</b> ',
            'textBody' => ' Text ',
            'templateId' => '12',
        ]);
        self::assertSame('Summer', $campaign->name);
        self::assertSame('loyal', $campaign->segmentKey);
        self::assertSame(['minOrders' => 3], $campaign->criteria);
        self::assertSame('Subject', $campaign->subject);
        self::assertSame('<b>Hi</b>', $campaign->htmlBody);
        self::assertSame('Text', $campaign->textBody);
        self::assertSame(12, $campaign->templateId);

        $template = MarketingTemplateInput::fromArray([
            'name' => ' Template ',
            'slug' => ' tpl ',
            'scenarioKey' => ' order_created ',
            'subjectTemplate' => ' Hello ',
            'htmlBody' => ' Body ',
            'textBody' => ' Text ',
            'isActive' => false,
        ]);
        self::assertSame('Template', $template->name);
        self::assertSame('tpl', $template->slug);
        self::assertSame('order_created', $template->scenarioKey);
        self::assertFalse($template->isActive);
    }

    public function testQuoteAdminDtosAndEmailAddress(): void
    {
        $email = EmailAddress::fromString('  ADA@EXAMPLE.COM ');
        self::assertSame('ada@example.com', $email->value());
        self::assertTrue($email->equals(EmailAddress::fromString('ada@example.com')));

        $emailInput = QuoteEmailInput::fromArray(['to' => ' ada@example.com ']);
        self::assertSame('ada@example.com', $emailInput->to?->value());

        $payload = QuotePayloadInput::fromArray([
            'customer' => ['name' => 'Ada'],
            'status' => ' sent ',
            'discountCents' => '100',
            'shippingCents' => '200',
            'conditions' => ' Net ',
            'validFrom' => '2026-07-01',
            'validUntil' => '2026-07-31',
            'items' => [['name' => 'Item'], 'bad'],
        ]);
        self::assertSame([
            'customer' => ['name' => 'Ada'],
            'status' => 'sent',
            'discountCents' => 100,
            'shippingCents' => 200,
            'conditions' => 'Net',
            'validFrom' => '2026-07-01',
            'validUntil' => '2026-07-31',
            'items' => [['name' => 'Item']],
        ], $payload->toPayload());

        $item = QuoteProductItemInput::fromArray([
            'productId' => '5',
            'quantity' => '2',
            'name' => ' Product ',
            'description' => ' Desc ',
            'unit' => ' u ',
            'unitPriceCents' => '1000',
            'discountCents' => '50',
            'vatRate' => '20',
            'vatRateBps' => '2000',
        ]);
        self::assertSame(5, $item->productId);
        self::assertSame([
            'name' => 'Product',
            'description' => 'Desc',
            'unit' => 'u',
            'quantity' => 2,
            'unitPriceCents' => 1000,
            'discountCents' => 50,
            'vatRate' => 20.0,
            'vatRateBps' => 2000,
        ], $item->toPayload());

        $serviceForm = new QuoteServiceFormData('Title', 'Desc', 'hour', 2, 'hour', 1000, 2000, true, null, null, null, true, false);
        self::assertSame('Title', $serviceForm->title);
        self::assertSame(1000, $serviceForm->priceCents);

        $status = QuoteStatusInput::fromArray(['status' => ' '.Quote::STATUS_ACCEPTED.' ']);
        self::assertSame(Quote::STATUS_ACCEPTED, $status->status);
    }

    public function testAuditAndNewsDtos(): void
    {
        $audit = new CreateAuditRequestDto();
        $audit->type = 'security';
        $audit->url = 'https://example.com';
        $audit->objectives = 'Check';
        self::assertSame('security', $audit->type);
        self::assertSame('https://example.com', $audit->url);
        self::assertSame('Check', $audit->objectives);

        $comment = CreateNewsCommentInput::fromArray(['content' => ' Hello ']);
        self::assertSame('Hello', $comment->content);

        $article = NewsArticleInput::fromArray([
            'title' => ' Title ',
            'slug' => ' slug ',
            'excerpt' => ' excerpt ',
            'content' => ' body ',
            'category' => ' tech ',
            'isPublished' => false,
            'publishedAt' => ' 2026-07-01 ',
        ]);
        self::assertSame('Title', $article->title);
        self::assertSame('slug', $article->slug);
        self::assertSame('excerpt', $article->excerpt);
        self::assertSame('body', $article->content);
        self::assertSame('tech', $article->category);
        self::assertFalse($article->isPublished);
        self::assertSame('2026-07-01', $article->publishedAt);
    }

    public function testEmailAddressRejectsInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Adresse email invalide.');
        EmailAddress::fromString('not-an-email');
    }
}
