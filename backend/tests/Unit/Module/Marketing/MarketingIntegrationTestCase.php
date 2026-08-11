<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing;

use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Notification\MarketingCampaignSender;
use App\Module\Marketing\Application\Outbox\DispatchMarketingCampaignRecipientEmailHandler;
use App\Module\Marketing\Application\Outbox\PrepareMarketingCampaignHandler;
use App\Module\Marketing\Application\Provider\EmailTemplateScenarioProvider;
use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Application\Workflow\MarketingCampaignService;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingAudienceQuery;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Notification\Domain\Entity\AccountNotificationEvent;
use App\Module\Notification\Infrastructure\Repository\AccountNotificationEventRepository;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use App\Shared\Infrastructure\Doctrine\DoctrineTransactionManager;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

abstract class MarketingIntegrationTestCase extends TestCase
{
    protected function campaignService(EntityManager $em, MailerInterface $mailer): MarketingCampaignService
    {
        unset($mailer);

        $persistence = new DoctrineUnitOfWork($em);
        $audiences = new MarketingAudienceProvider(new DoctrineMarketingAudienceQuery($em), new EmailTemplateScenarioProvider());

        return new MarketingCampaignService(
            $audiences,
            new MarketingCampaignSender(
                $persistence,
                new DoctrineTransactionManager($em),
                new Outbox($persistence),
            ),
        );
    }

    protected function notifier(EntityManager $em): UserCommunicationNotifier
    {
        return \App\Tests\Support\UserCommunicationNotifierFactory::create(
            $this,
            new AccountNotificationEventRepository($this->registry($em)),
            new DoctrineUnitOfWork($em),
            $this->createMock(MailerInterface::class),
            $this->createMock(AsyncMessageDispatcher::class),
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
            'https://front.example.test',
        );
    }

    /** @param list<string> $preferences */
    protected function user(string $email, array $preferences): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $user->setIsVerified(true);
        $user->setCommunicationPreferences($preferences);

        return $user;
    }

    protected function entityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
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
            $em->getClassMetadata(EmailCampaignRecipient::class),
            $em->getClassMetadata(OutboxEvent::class),
            $em->getClassMetadata(AccountNotificationEvent::class),
        ]);

        return $em;
    }

    protected function registry(EntityManager $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    protected function query(mixed $singleResult = null, mixed $oneOrNullResult = null, mixed $singleScalarResult = null): Query
    {
        $query = $this->createMock(Query::class);
        $query->method('getSingleResult')->willReturn($singleResult);
        $query->method('getOneOrNullResult')->willReturn($oneOrNullResult);
        $query->method('getSingleScalarResult')->willReturn($singleScalarResult);

        return $query;
    }

    protected function queryBuilder(Query $query): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        foreach (['select', 'from', 'andWhere', 'setParameter', 'orderBy', 'setMaxResults', 'join', 'leftJoin'] as $method) {
            $queryBuilder->method($method)->willReturnSelf();
        }
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}
