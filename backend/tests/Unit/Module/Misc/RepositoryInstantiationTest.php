<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Infrastructure\Repository\AuditChecklistItemRepository;
use App\Module\BetaTest\Domain\Entity\BetaCampaign;
use App\Module\BetaTest\Infrastructure\Repository\BetaCampaignRepository;
use App\Module\Cart\Domain\Entity\CartItem;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\Cart\Infrastructure\Repository\CartItemRepository;
use App\Module\Cart\Infrastructure\Repository\CartSessionRepository;
use App\Module\Catalog\Domain\Entity\StockMovement;
use App\Module\Catalog\Infrastructure\Repository\StockMovementRepository;
use App\Module\Comment\Domain\Entity\ProductComment;
use App\Module\Comment\Infrastructure\Repository\ProductCommentRepository;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRepository;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Order\Domain\Entity\RefundRequest;
use App\Module\Order\Domain\Entity\StripeWebhookEvent;
use App\Module\Order\Infrastructure\Repository\OrderItemRepository;
use App\Module\Order\Infrastructure\Repository\RefundRequestRepository;
use App\Module\Order\Infrastructure\Repository\StripeWebhookEventRepository;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Infrastructure\Repository\QuoteItemRepository;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Infrastructure\Repository\SupportRequestRepository;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\Training\Domain\Entity\TrainingRoadmapItem;
use App\Module\Training\Infrastructure\Repository\TrainingRoadmapItemRepository;

final class RepositoryInstantiationTest extends RepositoryTestCase
{
    public function testSimpleRepositoriesCanBeInstantiated(): void
    {
        self::assertInstanceOf(AuditChecklistItemRepository::class, $this->repository(AuditChecklistItemRepository::class, AuditChecklistItem::class));
        self::assertInstanceOf(BetaCampaignRepository::class, $this->repository(BetaCampaignRepository::class, BetaCampaign::class));
        self::assertInstanceOf(StockMovementRepository::class, $this->repository(StockMovementRepository::class, StockMovement::class));
        self::assertInstanceOf(ProductCommentRepository::class, $this->repository(ProductCommentRepository::class, ProductComment::class));
        self::assertInstanceOf(EmailCampaignRepository::class, $this->repository(EmailCampaignRepository::class, EmailCampaign::class));
        self::assertInstanceOf(OrderItemRepository::class, $this->repository(OrderItemRepository::class, OrderItem::class));
        self::assertInstanceOf(QuoteItemRepository::class, $this->repository(QuoteItemRepository::class, QuoteItem::class));
        self::assertInstanceOf(SupportRequestRepository::class, $this->repository(SupportRequestRepository::class, SupportRequest::class));
        self::assertInstanceOf(TrainingRoadmapItemRepository::class, $this->repository(TrainingRoadmapItemRepository::class, TrainingRoadmapItem::class));
        self::assertInstanceOf(WorkingDayConfigurationRepository::class, $this->repository(WorkingDayConfigurationRepository::class, WorkingDayConfiguration::class));
        self::assertInstanceOf(TradeInRequestRepository::class, $this->repository(TradeInRequestRepository::class, TradeInRequest::class));
        self::assertInstanceOf(StripeWebhookEventRepository::class, $this->repository(StripeWebhookEventRepository::class, StripeWebhookEvent::class));
        self::assertInstanceOf(RefundRequestRepository::class, $this->repository(RefundRequestRepository::class, RefundRequest::class));
        self::assertInstanceOf(CartItemRepository::class, $this->repository(CartItemRepository::class, CartItem::class));
        self::assertInstanceOf(CartSessionRepository::class, $this->repository(CartSessionRepository::class, CartSession::class));
        self::assertInstanceOf(EmailTemplateRepository::class, $this->repository(EmailTemplateRepository::class, EmailTemplate::class));
        self::assertInstanceOf(NewsArticleViewRepository::class, $this->repository(NewsArticleViewRepository::class, NewsArticleView::class));
        self::assertInstanceOf(PrestationRepository::class, $this->repository(PrestationRepository::class, Prestation::class));
    }
}
