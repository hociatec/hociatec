<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\News;

use App\Module\Admin\UI\News\Controller\CreateAdminNewsArticleController;
use App\Module\Admin\UI\News\Controller\DeleteAdminNewsArticleController;
use App\Module\Admin\UI\News\Controller\DeleteAdminNewsCommentController;
use App\Module\Admin\UI\News\Controller\GetAdminNewsArticleController;
use App\Module\Admin\UI\News\Controller\ListAdminNewsArticlesController;
use App\Module\Admin\UI\News\Controller\SendAdminNewsArticleEmailController;
use App\Module\Admin\UI\News\Controller\UpdateAdminNewsArticleController;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsArticleView;
use App\Module\News\Domain\Entity\NewsComment;
use App\Module\News\Infrastructure\Repository\NewsArticleRepository;
use App\Module\News\Infrastructure\Repository\NewsArticleViewRepository;
use App\Module\News\Infrastructure\Repository\NewsCommentRepository;
use App\Module\News\Application\Service\NewsArticleWriter;
use App\Module\News\Application\Projection\NewsFormatter;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class AdminNewsControllersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testAdminNewsControllersCoverCrudCommentsAndEmailDispatch(): void
    {
        $user = $this->persistUser();
        $article = $this->persistArticle('Existing', 'existing', true);
        $comment = new NewsComment($article, $user, 'Nice');
        $this->entityManager()->persist($comment);
        $this->entityManager()->flush();

        $formatter = new NewsFormatter($this->views());
        $writer = $this->writer();

        $listPayload = $this->payload((new ListAdminNewsArticlesController($this->articles(), $formatter))(Request::create('/?q=exist&page=1&perPage=5')));
        self::assertSame('Existing', $listPayload['data']['items'][0]['title']);
        self::assertSame(1, $listPayload['data']['meta']['total']);

        self::assertSame(404, (new GetAdminNewsArticleController($this->articles(), $formatter))(999)->getStatusCode());
        self::assertSame('Existing', $this->payload((new GetAdminNewsArticleController($this->articles(), $formatter))((int) $article->getId()))['data']['article']['title']);

        $create = new CreateAdminNewsArticleController($writer, $formatter);
        self::assertSame(400, $create($this->jsonRequest(['title' => '', 'slug' => '', 'excerpt' => '', 'content' => '']))->getStatusCode());
        $created = $create($this->jsonRequest($this->articlePayload('Created', 'created', true)));
        self::assertSame(201, $created->getStatusCode());
        self::assertSame('Created', $this->payload($created)['data']['article']['title']);

        $update = new UpdateAdminNewsArticleController($this->articles(), $writer, $formatter);
        self::assertSame(404, $update(999, $this->jsonRequest([]))->getStatusCode());
        $updated = $update((int) $article->getId(), $this->jsonRequest($this->articlePayload('Updated', 'updated', false), 'PUT'));
        self::assertSame(200, $updated->getStatusCode());
        self::assertFalse($this->payload($updated)['data']['article']['isPublished']);

        $send = new SendAdminNewsArticleEmailController($this->articles(), $writer);
        self::assertSame(404, $send(999)->getStatusCode());
        self::assertSame(400, $send((int) $article->getId())->getStatusCode());
        $published = $this->persistArticle('Published', 'published', true);
        self::assertSame(200, $send((int) $published->getId())->getStatusCode());

        self::assertSame(404, (new DeleteAdminNewsCommentController($this->comments(), $writer))(999)->getStatusCode());
        self::assertSame(200, (new DeleteAdminNewsCommentController($this->comments(), $writer))((int) $comment->getId())->getStatusCode());

        self::assertSame(404, (new DeleteAdminNewsArticleController($this->articles(), $writer))(999)->getStatusCode());
        self::assertSame(200, (new DeleteAdminNewsArticleController($this->articles(), $writer))((int) $published->getId())->getStatusCode());
    }

    /** @return array<string,mixed> */
    private function articlePayload(string $title, string $slug, bool $published): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'category' => 'Product',
            'isPublished' => $published,
        ];
    }

    private function writer(): NewsArticleWriter
    {
        $users = $this->createMock(UserRepository::class);
        $users->method('findNewsEmailSubscribers')->willReturn([$this->persistUser('subscriber@example.test')]);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        return new NewsArticleWriter(new DoctrineUnitOfWork($this->entityManager()), $users, $bus);
    }

    private function persistUser(string $email = 'news-admin@example.test'): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function persistArticle(string $title, string $slug, bool $published): NewsArticle
    {
        $article = (new NewsArticle($title, $slug, 'Excerpt', 'Content'))
            ->setCategory('Product')
            ->setPublished($published)
            ->setPublishedAt($published ? new \DateTimeImmutable('2026-08-01T10:00:00+00:00') : null);
        $this->entityManager()->persist($article);
        $this->entityManager()->flush();

        return $article;
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
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

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    private function articles(): NewsArticleRepository
    {
        return new NewsArticleRepository($this->registry());
    }

    private function comments(): NewsCommentRepository
    {
        return new NewsCommentRepository($this->registry());
    }

    private function views(): NewsArticleViewRepository
    {
        return new NewsArticleViewRepository($this->registry());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }
}
