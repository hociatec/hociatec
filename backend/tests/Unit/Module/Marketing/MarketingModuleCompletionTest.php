<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Application\Provider\EmailTemplateScenarioProvider;
use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Application\Notification\MarketingCampaignSender;
use App\Module\Marketing\Application\Workflow\MarketingCampaignService;
use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingAudienceQuery;
use App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingRecipientContextQuery;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;

final class MarketingModuleCompletionTest extends TestCase
{
    public function testCampaignServiceDelegatesAudiencePreviewResolveAndSend(): void
    {
        $em = $this->entityManager();
        $newsUser = $this->user('news@example.com', [CommunicationPreferences::NEWS_EMAIL]);
        $silentUser = $this->user('silent@example.com', []);
        $em->persist($newsUser);
        $em->persist($silentUser);
        $em->flush();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(static function (Email $email): bool {
            return 'Bonjour Ada' === $email->getSubject()
                && str_contains($email->getHtmlBody() ?? '', 'news@example.com')
                && str_contains($email->getTextBody() ?? '', 'https://front.example.test');
        }));

        $service = $this->campaignService($em, $mailer);

        self::assertArrayHasKey('all_verified_users', $service->getSegmentDefinitions());
        self::assertSame(2, $service->previewAudience('all_verified_users', [])['count']);
        self::assertCount(1, $service->resolveRecipients('all_verified_users', [], 1));

        $template = new EmailTemplate('News', 'news', 'marketing_news', 'Sujet', '<p>Body</p>');
        $em->persist($template);
        $em->flush();
        $campaign = $service->sendCampaign(
            'Campagne aout',
            'all_verified_users',
            [],
            'Bonjour {{first_name}}',
            '<p>{{email}}</p>',
            '{{app_frontend_url}}',
            $template,
            'admin@example.com',
        );

        self::assertInstanceOf(EmailCampaign::class, $campaign);
        self::assertSame(2, $campaign->getRecipientsCount());
        self::assertSame('admin@example.com', $campaign->getCreatedByEmail());
    }

    public function testRecipientContextProviderCoversEmptyOrderFallbacks(): void
    {
        $em = $this->entityManager();
        $user = $this->user('empty@example.com', []);
        $em->persist($user);
        $em->flush();

        $context = (new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($em), 'https://front.example.test/'))->provide($user);

        self::assertSame('0', $context['order_count']);
        self::assertSame('', $context['last_order_date']);
        self::assertSame('', $context['last_order_number']);
        self::assertSame('', $context['days_since_last_order']);
        self::assertSame('https://front.example.test', $context['app_frontend_url']);
    }

    public function testRecipientContextProviderIncludesOrderStatsAndPendingReviews(): void
    {
        $em = $this->entityManager();
        $user = $this->user('buyer@example.com', []);
        $order = (new Order('ORD-MKT-1', $user))->setTotalPriceCents(12345);
        $order->addItem(new OrderItem('Laptop', 'SKU-1', 10000, 1));
        $em->persist($user);
        $em->persist($order);
        $em->flush();

        $context = (new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($em), 'https://front.example.test'))->provide($user);

        self::assertSame('1', $context['order_count']);
        self::assertSame('123,45', $context['total_spent_eur']);
        self::assertSame('ORD-MKT-1', $context['last_order_number']);
        self::assertSame('1', $context['pending_reviews_count']);
    }

    public function testRecipientContextProviderFormatsDateTimeOrderStats(): void
    {
        $user = $this->user('dated@example.com', []);
        $order = new Order('ORD-DATE-1', $user);
        $lastOrderAt = new \DateTimeImmutable('2026-07-15T10:00:00+00:00');
        $queries = [
            $this->query(singleResult: ['ordersCount' => 2, 'lastOrderAt' => $lastOrderAt, 'totalSpentCents' => 9900]),
            $this->query(oneOrNullResult: $order),
            $this->query(singleScalarResult: 0),
        ];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            $this->queryBuilder($queries[0]),
            $this->queryBuilder($queries[1]),
            $this->queryBuilder($queries[2]),
        );

        $context = (new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($entityManager), 'https://front.example.test'))->provide($user);

        self::assertSame('15/07/2026', $context['last_order_date']);
        self::assertSame('ORD-DATE-1', $context['last_order_number']);
        self::assertNotSame('', $context['days_since_last_order']);
    }

    private function campaignService(EntityManager $em, MailerInterface $mailer): MarketingCampaignService
    {
        $persistence = new DoctrineUnitOfWork($em);
        $audiences = new MarketingAudienceProvider(new DoctrineMarketingAudienceQuery($em), new EmailTemplateScenarioProvider());

        return new MarketingCampaignService(
            $audiences,
            new MarketingCampaignSender(
                $audiences,
                new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($em), 'https://front.example.test'),
                new MarketingTemplateRenderer(),
                $mailer,
                $this->notifier($em),
                $persistence,
                'noreply@example.com',
            ),
        );
    }

    private function notifier(EntityManager $em): UserCommunicationNotifier
    {
        return new UserCommunicationNotifier(
            new AccountNotificationEventRepository($this->registry($em)),
            new DoctrineUnitOfWork($em),
            $this->createMock(MailerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    /** @param list<string> $preferences */
    private function user(string $email, array $preferences): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setIsVerified(true);
        $user->setCommunicationPreferences($preferences);

        return $user;
    }

    private function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $em = new EntityManager($connection, $config);
        (new SchemaTool($em))->createSchema([
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Order::class),
            $em->getClassMetadata(OrderItem::class),
            $em->getClassMetadata(ProductRating::class),
            $em->getClassMetadata(Category::class),
            $em->getClassMetadata(Brand::class),
            $em->getClassMetadata(Product::class),
            $em->getClassMetadata(EmailTemplate::class),
            $em->getClassMetadata(EmailCampaign::class),
            $em->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $em;
    }

    private function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    private function query(mixed $singleResult = null, mixed $oneOrNullResult = null, mixed $singleScalarResult = null): Query
    {
        $query = $this->createMock(Query::class);
        $query->method('getSingleResult')->willReturn($singleResult);
        $query->method('getOneOrNullResult')->willReturn($oneOrNullResult);
        $query->method('getSingleScalarResult')->willReturn($singleScalarResult);

        return $query;
    }

    private function queryBuilder(Query $query): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        foreach (['select', 'from', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'join', 'leftJoin'] as $method) {
            $queryBuilder->method($method)->willReturnSelf();
        }
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}
