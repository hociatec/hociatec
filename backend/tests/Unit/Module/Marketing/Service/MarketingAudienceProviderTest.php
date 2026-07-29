<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing\Service;

use App\Module\Marketing\Service\EmailTemplateScenarioProvider;
use App\Module\Marketing\Service\MarketingAudienceProvider;
use App\Module\User\Entity\User;
use App\Shared\Persistence\DoctrinePersistence;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MarketingAudienceProviderTest extends TestCase
{
    #[DataProvider('segmentPreviewCases')]
    public function testPreviewBuildsSegmentDescriptionsForAllKnownSegments(
        string $segment,
        array $criteria,
        string $expectedDescription,
    ): void {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 7);

        $provider = $this->providerWithQueryBuilders([
            $this->queryBuilderReturning([$user]),
            $this->queryBuilderReturning([$user]),
        ]);

        $preview = $provider->preview($segment, $criteria);

        self::assertSame(1, $preview['count']);
        self::assertSame([[
            'id' => 7,
            'email' => 'ada@example.com',
            'fullName' => 'Ada Lovelace',
        ]], $preview['recipients']);
        self::assertSame($expectedDescription, $preview['description']);
    }

    public function testGetSegmentDefinitionsDelegatesToScenarioProvider(): void
    {
        $provider = $this->providerWithQueryBuilders([]);
        $definitions = $provider->getSegmentDefinitions();

        self::assertSame((new EmailTemplateScenarioProvider())->getCampaignScenarioDefinitions(), $definitions);
    }

    public function testResolveRecipientsRejectsUnknownSegment(): void
    {
        $provider = $this->providerWithQueryBuilders([
            $this->queryBuilderReturning([]),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Segment marketing inconnu.');
        $provider->resolveRecipients('unknown_segment', []);
    }

    public function testPreviewAppliesLimitToRecipientPreviewQuery(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 7);

        $limitCalls = [];
        $provider = $this->providerWithQueryBuilders([
            $this->queryBuilderReturning([$user]),
            $this->queryBuilderReturning([$user], $limitCalls),
        ]);

        $provider->preview('all_verified_users', []);

        self::assertContains(['setMaxResults', 10], $limitCalls);
    }

    /** @return list<array{string, array<string, mixed>, string}> */
    public static function segmentPreviewCases(): array
    {
        return [
            ['all_verified_users', [], 'Tous les comptes vérifiés.'],
            ['recent_verified_users', ['registeredDays' => 5], 'Comptes vérifiés créés depuis moins de 7 jours.'],
            ['customers_with_orders', ['minimumOrders' => 4], 'Clients avec au moins 4 commande(s).'],
            ['loyal_customers', ['minimumOrders' => 1], 'Clients avec au moins 2 commandes.'],
            ['single_order_customers', [], 'Clients ayant exactement une commande.'],
            ['recent_customers', ['recentDays' => 21], 'Clients ayant commandé au cours des 21 derniers jours.'],
            ['high_value_customers', ['minimumTotalCents' => 15500], 'Clients avec au moins 155.00 EUR de commandes cumulées.'],
            ['customers_without_review', [], 'Clients ayant commandé mais sans avis publié sur au moins un article.'],
            ['customers_with_pending_reviews', ['minimumPendingReviews' => 3], 'Clients avec au moins 3 avis en attente.'],
            ['inactive_customers', ['inactiveDays' => 20], 'Clients inactifs depuis plus de 30 jours.'],
            ['verified_without_orders', [], 'Comptes vérifiés sans aucune commande.'],
            ['verified_without_orders_recent', ['registeredDays' => 45], 'Comptes vérifiés créés depuis moins de 45 jours et sans commande.'],
        ];
    }

    /**
     * @param list<QueryBuilder> $builders
     */
    private function providerWithQueryBuilders(array $builders): MarketingAudienceProvider
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturnOnConsecutiveCalls(...$builders);

        return new MarketingAudienceProvider(
            new DoctrinePersistence($entityManager),
            new EmailTemplateScenarioProvider(),
        );
    }

    /**
     * @param list<User> $result
     * @param list<array<int, mixed>>|null $calls
     */
    private function queryBuilderReturning(array $result, ?array &$calls = null): QueryBuilder
    {
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMock();
        $query->method('getResult')->willReturn($result);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'select',
                'from',
                'andWhere',
                'setParameter',
                'orderBy',
                'join',
                'groupBy',
                'having',
                'leftJoin',
                'setMaxResults',
                'getQuery',
            ])
            ->getMock();

        foreach (['select', 'from', 'andWhere', 'setParameter', 'orderBy', 'join', 'groupBy', 'having', 'leftJoin', 'setMaxResults'] as $method) {
            $qb->method($method)->willReturnCallback(function (...$args) use ($qb, $method, &$calls) {
                if (null !== $calls) {
                    $calls[] = array_merge([$method], $args);
                }

                return $qb;
            });
        }

        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
