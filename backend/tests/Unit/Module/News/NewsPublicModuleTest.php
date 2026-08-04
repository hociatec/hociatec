<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\News;

use App\Module\News\UI\Controller\PublicApi\CreateNewsCommentController;
use App\Module\News\UI\Controller\PublicApi\ListNewsArticlesController;
use App\Module\News\UI\Controller\PublicApi\ListNewsCommentsController;
use App\Module\News\UI\Controller\PublicApi\ShowNewsArticleController;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Domain\Entity\NewsComment;
use App\Module\News\Infrastructure\Repository\NewsArticleRepository;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;
use App\Module\News\Infrastructure\Repository\NewsCommentRepository;
use App\Module\News\Application\Workflow\NewsArticleViewTracker;
use App\Module\News\Application\Writer\NewsCommentWriter;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class NewsPublicModuleTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testRepositoriesFormatterViewTrackerAndPublicReadControllers(): void
    {
        [$published, $draft, $future, $user] = $this->seedNews();
        $comment = new NewsComment($published, $user, '  Premier commentaire  ');
        $this->entityManager()->persist($comment);
        $this->entityManager()->flush();

        $articles = $this->repository(NewsArticleRepository::class);
        $comments = $this->repository(NewsCommentRepository::class);
        $views = $this->repository(NewsArticleViewRepository::class);
        $formatter = new NewsFormatter($views);

        self::assertSame(1, $articles->countPublished('guide'));
        self::assertSame([$published], $articles->findPublished('guide', 100, -20));
        self::assertSame(3, $articles->countForAdmin(''));
        self::assertCount(3, $articles->findForAdmin('', 1000, -10));
        self::assertSame($published, $articles->findPublishedBySlug(' "guide-repair!" '));
        self::assertNull($articles->findPublishedBySlug($draft->getSlug()));
        self::assertNull($articles->findPublishedBySlug($future->getSlug()));

        $tracker = new NewsArticleViewTracker($views, new DoctrineUnitOfWork($this->entityManager()));
        $tracker->track($published, '');
        self::assertSame(0, $views->countUniqueForArticle($published));
        $tracker->track($published, '192.0.2.10');
        $tracker->track($published, '192.0.2.10');
        self::assertSame(1, $views->countUniqueForArticle($published));
        self::assertNotNull($views->findOneForArticleAndIpHash($published, hash('sha256', '192.0.2.10')));

        $articlePayload = $formatter->article($published);
        self::assertSame('Guide reparation', $articlePayload['title']);
        self::assertSame(1, $articlePayload['viewsCount']);

        $commentPayload = $formatter->comment($comment);
        self::assertSame('Premier commentaire', $commentPayload['content']);
        self::assertSame('Ada Lovelace', $commentPayload['author']['name']);

        $listResponse = (new ListNewsArticlesController($articles, $formatter))(new Request(['q' => 'guide', 'page' => '0', 'perPage' => '100']));
        $listPayload = $this->json($listResponse);
        self::assertSame(Response::HTTP_OK, $listResponse->getStatusCode());
        self::assertSame(1, $listPayload['data']['meta']['page']);
        self::assertSame(30, $listPayload['data']['meta']['perPage']);
        self::assertSame('guide-repair', $listPayload['data']['items'][0]['slug']);

        $showController = new ShowNewsArticleController($articles, $formatter, $tracker);
        self::assertSame(Response::HTTP_NOT_FOUND, $showController('missing', new Request())->getStatusCode());
        $showResponse = $showController('guide-repair', Request::create('/api/public/news/guide-repair', 'GET', [], [], [], ['REMOTE_ADDR' => '198.51.100.1']));
        self::assertSame(Response::HTTP_OK, $showResponse->getStatusCode());
        self::assertSame(2, $views->countUniqueForArticle($published));

        $commentsResponse = (new ListNewsCommentsController($articles, $comments, $formatter))('guide-repair', new Request(['page' => '1', 'perPage' => '50']));
        $commentsPayload = $this->json($commentsResponse);
        self::assertSame(Response::HTTP_OK, $commentsResponse->getStatusCode());
        self::assertSame(1, $commentsPayload['data']['meta']['total']);
        self::assertSame('Premier commentaire', $commentsPayload['data']['items'][0]['content']);
        self::assertSame(Response::HTTP_NOT_FOUND, (new ListNewsCommentsController($articles, $comments, $formatter))('missing', new Request())->getStatusCode());
    }

    public function testCreateNewsCommentControllerHandlesAuthenticationValidationAndPersistence(): void
    {
        [$published, , , $user] = $this->seedNews();
        $articles = $this->repository(NewsArticleRepository::class);
        $formatter = new NewsFormatter($this->repository(NewsArticleViewRepository::class));
        $controller = new CreateNewsCommentController($articles, new NewsCommentWriter(new DoctrineUnitOfWork($this->entityManager())), $formatter);

        self::assertSame(Response::HTTP_NOT_FOUND, $controller('missing', new Request([], [], [], [], [], [], '{"content":"Bonjour"}'))->getStatusCode());

        $controller->setContainer($this->containerForUser(null));
        self::assertSame(Response::HTTP_UNAUTHORIZED, $controller($published->getSlug(), new Request([], [], [], [], [], [], '{"content":"Bonjour"}'))->getStatusCode());

        $controller->setContainer($this->containerForUser($user));
        $invalid = $controller($published->getSlug(), new Request([], [], [], [], [], [], '{"content":"No"}'));
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $invalid->getStatusCode());

        $created = $controller($published->getSlug(), new Request([], [], [], [], [], [], '{"content":"  Merci pour le guide  "}'));
        $payload = $this->json($created);
        self::assertSame(Response::HTTP_CREATED, $created->getStatusCode());
        self::assertSame('Commentaire publié.', $payload['message']);
        self::assertSame('Merci pour le guide', $payload['data']['comment']['content']);
        self::assertSame(1, $this->repository(NewsCommentRepository::class)->countForArticle($published));
    }

    /** @return array{NewsArticle,NewsArticle,NewsArticle,User} */
    private function seedNews(): array
    {
        $user = new User('ada@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $published = (new NewsArticle(' Guide reparation ', 'guide-repair', 'Excerpt guide', 'Content guide'))->setCategory('Guides');
        $draft = (new NewsArticle('Draft', 'draft-news', 'Excerpt', 'Content'))->setPublished(false)->setPublishedAt(null);
        $future = (new NewsArticle('Future', 'future-news', 'Excerpt', 'Content'))->setPublished(true)->setPublishedAt(new \DateTimeImmutable('+1 day'));

        foreach ([$user, $published, $draft, $future] as $entity) {
            $this->entityManager()->persist($entity);
        }
        $this->entityManager()->flush();

        return [$published, $draft, $future, $user];
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(NewsArticle::class),
            $entityManager->getClassMetadata(NewsComment::class),
            $entityManager->getClassMetadata(NewsArticleView::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     *
     * @return T
     */
    private function repository(string $repositoryClass): object
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return new $repositoryClass($registry);
    }

    private function containerForUser(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if ($user instanceof User) {
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        }

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }

    /** @return array<string, mixed> */
    private function json(\Symfony\Component\HttpFoundation\JsonResponse $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
