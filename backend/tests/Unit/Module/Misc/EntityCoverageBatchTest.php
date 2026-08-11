<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Entity\BugReportActivity;
use App\Module\BetaTest\Domain\Entity\BugReportComment;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignContentSnapshot;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Domain\Entity\NewsComment;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class EntityCoverageBatchTest extends TestCase
{
    public function testBetaTestActivityAndCommentExposeState(): void
    {
        $reporter = $this->user('reporter@example.com', 'Reporter');
        $admin = $this->user('admin@example.com', 'Admin');
        $campaign = new BetaCampaign('Campagne', 'Desc');
        $bug = new BugReport($reporter, $campaign, 'Titre', 'Desc', 'Expected', 'Actual', 'high', 'https://example.com');

        $activity = new BugReportActivity($bug, $admin, 'status_changed', 'submitted', 'planned', ' planned ');
        $comment = new BugReportComment($bug, $reporter, 'Commentaire');

        self::assertNull($activity->getId());
        self::assertSame($bug, $activity->getBugReport());
        self::assertSame($admin, $activity->getActor());
        self::assertSame('status_changed', $activity->getAction());
        self::assertSame('submitted', $activity->getFromValue());
        self::assertSame('planned', $activity->getToValue());
        self::assertSame('planned', $activity->getMessage());
        self::assertInstanceOf(\DateTimeImmutable::class, $activity->getCreatedAt());

        $blankMessageActivity = new BugReportActivity($bug, null, 'created', null, null, '   ');
        self::assertNull($blankMessageActivity->getActor());
        self::assertNull($blankMessageActivity->getMessage());

        self::assertNull($comment->getId());
        self::assertSame($bug, $comment->getBugReport());
        self::assertSame($reporter, $comment->getAuthor());
        self::assertSame('Commentaire', $comment->getContent());
        self::assertInstanceOf(\DateTimeImmutable::class, $comment->getCreatedAt());
    }

    public function testStockMovementFavoriteMarketingNewsNotificationAndOrderHelpers(): void
    {
        $user = $this->user('ada@example.com', 'Ada');
        $category = new Category('Phones', 'phones');
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, $category);
        $stockMovement = new StockMovement($product, 5, 10, 15, '  ', $user);
        $favorite = new Favorite($user, $product);

        self::assertNull($stockMovement->getId());
        self::assertSame($product, $stockMovement->getProduct());
        self::assertSame(5, $stockMovement->getDelta());
        self::assertSame(10, $stockMovement->getStockBefore());
        self::assertSame(15, $stockMovement->getStockAfter());
        self::assertSame('adjustment', $stockMovement->getReason());
        self::assertNull($stockMovement->getNote());
        self::assertSame($user, $stockMovement->getActor());
        self::assertInstanceOf(\DateTimeImmutable::class, $stockMovement->getCreatedAt());
        $stockMovement->setNote(' note ');
        self::assertSame('note', $stockMovement->getNote());

        self::assertNull($favorite->getId());
        self::assertSame($user, $favorite->getUser());
        self::assertSame($product, $favorite->getProduct());
        self::assertInstanceOf(\DateTimeImmutable::class, $favorite->getCreatedAt());

        $template = new EmailTemplate('Template', 'template', 'order_created', 'Sujet', '<p>HTML</p>', 'Texte');
        $templateUpdatedAt = $template->getUpdatedAt();
        self::assertNull($template->getId());
        $template
            ->setName('Template 2')
            ->setSlug('template-2')
            ->setScenarioKey('invoice')
            ->setSubjectTemplate('Sujet 2')
            ->setHtmlBody('<p>HTML 2</p>')
            ->setTextBody('Texte 2')
            ->setIsActive(false);
        self::assertSame('Template 2', $template->getName());
        self::assertSame('template-2', $template->getSlug());
        self::assertSame('invoice', $template->getScenarioKey());
        self::assertSame('Sujet 2', $template->getSubjectTemplate());
        self::assertSame('<p>HTML 2</p>', $template->getHtmlBody());
        self::assertSame('Texte 2', $template->getTextBody());
        self::assertFalse($template->isActive());
        self::assertInstanceOf(\DateTimeImmutable::class, $template->getCreatedAt());
        usleep(1000);
        $template->touch();
        self::assertGreaterThanOrEqual($templateUpdatedAt, $template->getUpdatedAt());

        $campaign = new EmailCampaign(
            'Campagne',
            'all_users',
            ['country' => 'FR'],
            new EmailCampaignContentSnapshot('Sujet snapshot', '<p>Snapshot</p>', 'Text snapshot'),
            0,
            'admin@example.com',
            $template,
        );
        $campaignUpdatedAt = $campaign->getUpdatedAt();
        self::assertNull($campaign->getId());
        self::assertSame('Campagne', $campaign->getName());
        self::assertSame('all_users', $campaign->getSegmentKey());
        self::assertSame(['country' => 'FR'], $campaign->getCriteria());
        self::assertSame($template, $campaign->getTemplate());
        self::assertSame('Sujet snapshot', $campaign->getSubjectSnapshot());
        self::assertSame('<p>Snapshot</p>', $campaign->getHtmlSnapshot());
        self::assertSame('Text snapshot', $campaign->getTextSnapshot());
        self::assertSame(0, $campaign->getRecipientsCount());
        self::assertSame('admin@example.com', $campaign->getCreatedByEmail());
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getSentAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $campaign->getCreatedAt());
        usleep(1000);
        $campaign->touch();
        self::assertGreaterThanOrEqual($campaignUpdatedAt, $campaign->getUpdatedAt());

        $article = new NewsArticle(' Title ', ' slug ', ' Excerpt ', ' Content ');
        $articleUpdatedAt = $article->getUpdatedAt();
        self::assertNull($article->getId());
        $article
            ->setTitle(' Updated ')
            ->setSlug(' updated-slug ')
            ->setExcerpt(' Updated excerpt ')
            ->setContent(' Updated content ')
            ->setCategory('  Guides  ')
            ->setPublished(false)
            ->setPublishedAt(new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
        self::assertSame('Updated', $article->getTitle());
        self::assertSame('updated-slug', $article->getSlug());
        self::assertSame('Updated excerpt', $article->getExcerpt());
        self::assertSame('Updated content', $article->getContent());
        self::assertSame('Guides', $article->getCategory());
        self::assertFalse($article->isPublished());
        self::assertSame('2026-07-01T10:00:00+00:00', $article->getPublishedAt()?->format(DATE_ATOM));
        self::assertInstanceOf(\DateTimeImmutable::class, $article->getCreatedAt());
        $article->setCategory('   ');
        self::assertNull($article->getCategory());
        usleep(1000);
        $article->touch();
        self::assertGreaterThanOrEqual($articleUpdatedAt, $article->getUpdatedAt());

        $view = new NewsArticleView($article, 'hash');
        self::assertObjectHasProperty('id', $view);
        $before = $this->readProperty($view, 'viewsCount');
        $view->markViewed();
        self::assertSame($before + 1, $this->readProperty($view, 'viewsCount'));
        self::assertSame('hash', $this->readProperty($view, 'ipHash'));
        self::assertSame($article, $this->readProperty($view, 'article'));
        self::assertInstanceOf(\DateTimeImmutable::class, $this->readProperty($view, 'firstViewedAt'));
        self::assertInstanceOf(\DateTimeImmutable::class, $this->readProperty($view, 'lastViewedAt'));

        $comment = new NewsComment($article, $user, ' contenu ');
        self::assertNull($comment->getId());
        self::assertSame($article, $comment->getArticle());
        self::assertSame($user, $comment->getAuthor());
        self::assertSame('contenu', $comment->getContent());
        self::assertInstanceOf(\DateTimeImmutable::class, $comment->getCreatedAt());

        $notification = new AccountNotificationEvent($user, 'key', 'Titre', 'Message', '/target', 'order');
        self::assertNull($notification->getId());
        self::assertSame('key', $notification->getKey());
        self::assertSame('Titre', $notification->getTitle());
        self::assertSame('Message', $notification->getMessage());
        self::assertSame('/target', $notification->getTargetUrl());
        self::assertSame('order', $notification->getType());
        self::assertInstanceOf(\DateTimeImmutable::class, $notification->getCreatedAt());

        $order = new Order('ORD-1', $user);
        $refund = new RefundRequest($order, 2000, $user);
        $refundUpdatedAt = $refund->getUpdatedAt();
        $refund
            ->setPaymentId(12)
            ->setAmountCents(100)
            ->setCurrencyCode('usd-extra')
            ->setStatus(RefundRequest::STATUS_APPROVED)
            ->setReason(' reason ')
            ->setInternalNotes(' notes ')
            ->setStripeRefundId(' stripe ');
        self::assertNull($refund->getId());
        self::assertSame($order, $refund->getOrder());
        self::assertSame(12, $refund->getPaymentId());
        self::assertSame(100, $refund->getAmountCents());
        self::assertSame('USD', $refund->getCurrencyCode());
        self::assertSame(RefundRequest::STATUS_APPROVED, $refund->getStatus());
        self::assertSame('reason', $refund->getReason());
        self::assertSame('notes', $refund->getInternalNotes());
        self::assertSame('stripe', $refund->getStripeRefundId());
        self::assertSame($user, $refund->getActor());
        self::assertInstanceOf(\DateTimeImmutable::class, $refund->getCreatedAt());
        usleep(1000);
        $refund->touch();
        self::assertGreaterThanOrEqual($refundUpdatedAt, $refund->getUpdatedAt());

        $event = new StripeWebhookEvent('evt_1', 'payment_intent.succeeded');
        self::assertNull($event->getId());
        self::assertSame('evt_1', $event->getStripeEventId());
        self::assertSame('processing', $event->getStatus());
        self::assertFalse($event->isProcessed());
        $event->markFailed(str_repeat('x', 2500));
        self::assertSame('failed', $event->getStatus());
        self::assertSame(2000, mb_strlen((string) $this->readProperty($event, 'errorMessage')));
        $event->markProcessed();
        self::assertTrue($event->isProcessed());
        self::assertSame('processed', $event->getStatus());
        self::assertNull($this->readProperty($event, 'errorMessage'));
        self::assertInstanceOf(\DateTimeImmutable::class, $this->readProperty($event, 'receivedAt'));
        self::assertInstanceOf(\DateTimeImmutable::class, $this->readProperty($event, 'processedAt'));
    }

    private function user(string $email, string $firstName): User
    {
        return new User($email, $firstName, 'User', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionObject($object);

        return $reflection->getProperty($property)->getValue($object);
    }
}
