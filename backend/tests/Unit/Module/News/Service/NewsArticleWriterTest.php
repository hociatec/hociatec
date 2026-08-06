<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\News\Service;

use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\News\Application\Message\NewsArticlePublishedEmailMessage;
use App\Module\News\Application\Writer\NewsArticleWriter;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class NewsArticleWriterTest extends TestCase
{
    public function testCreateUpdateDeleteAndEmailDispatch(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(NewsArticle::class));
        $entityManager->expects(self::exactly(4))->method('flush');
        $entityManager->expects(self::once())->method('remove')->with(self::isInstanceOf(NewsArticle::class));
        $persistence = new DoctrineUnitOfWork($entityManager);

        $userRepository = $this->createMock(UserRepository::class);
        $subscriber = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($subscriber, 7);
        $subscriber->setCommunicationPreferences(['news_email']);
        $unsaved = new User('ghost@example.com', 'Ghost', 'User', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $userRepository->expects(self::exactly(3))->method('findNewsEmailSubscribers')->willReturn([$subscriber, $unsaved]);

        $messages = [];
        $bus = new class($messages) implements MessageBusInterface {
            public array $messages = [];

            public function __construct(array &$messages)
            {
                $this->messages = &$messages;
            }

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->messages[] = $message;

                return new Envelope($message, $stamps);
            }
        };

        $writer = new NewsArticleWriter($persistence, $userRepository, $bus);

        $created = $writer->create(new NewsArticleInput('Title', 'slug', 'Excerpt', 'Content', 'Guides', true, null));
        self::assertSame('Title', $created->getTitle());
        self::assertTrue($created->isPublished());
        self::assertCount(1, $messages);
        self::assertInstanceOf(NewsArticlePublishedEmailMessage::class, $messages[0]);
        self::assertSame('slug', $messages[0]->slug);

        $updated = $writer->update($created, new NewsArticleInput('Title 2', 'slug-2', 'Excerpt 2', 'Content 2', 'News', true, '2026-07-29T10:00:00+00:00'));
        self::assertSame('Title 2', $updated->getTitle());
        self::assertSame('slug-2', $updated->getSlug());
        self::assertSame('News', $updated->getCategory());
        self::assertCount(1, $messages);

        $draft = new NewsArticle('Draft', 'draft', 'Excerpt', 'Content');
        $draft->setPublished(false)->setPublishedAt(null);
        $writer->update($draft, new NewsArticleInput('Draft', 'draft', 'Excerpt', 'Content', null, true, null));
        self::assertCount(2, $messages);

        $writer->sendPublishedEmails($created);
        self::assertCount(3, $messages);

        $writer->delete($created);

        try {
            $writer->sendPublishedEmails($draft->setPublished(false)->setPublishedAt(null));
            self::fail('Expected exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Seule une actualité publiée peut être envoyée par e-mail.', $exception->getMessage());
        }
    }

    public function testWriterRejectsMissingFields(): void
    {
        $writer = new NewsArticleWriter(
            new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class)),
            $this->createMock(UserRepository::class),
            $this->createMock(MessageBusInterface::class),
        );

        try {
            $writer->create(new NewsArticleInput('', '', '', '', null, true, null));
            self::fail('Expected exception.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Titre, slug, résumé et contenu sont obligatoires.', $exception->getMessage());
        }
    }

    public function testCreatePublishedAtAndDraftUpdateDoNotDispatch(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(NewsArticle::class));
        $entityManager->expects(self::exactly(2))->method('flush');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findNewsEmailSubscribers')->willReturn([]);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $writer = new NewsArticleWriter(new DoctrineUnitOfWork($entityManager), $users, $bus);

        $published = $writer->create(new NewsArticleInput(
            'Published',
            'published',
            'Excerpt',
            'Content',
            null,
            true,
            '2026-07-29T12:00:00+00:00',
        ));
        self::assertSame('2026-07-29T12:00:00+00:00', $published->getPublishedAt()?->format(DATE_ATOM));

        $draft = new NewsArticle('Draft', 'draft', 'Excerpt', 'Content');
        $draft->setPublished(false)->setPublishedAt(new \DateTimeImmutable('2026-07-28T12:00:00+00:00'));
        $writer->update($draft, new NewsArticleInput('Draft', 'draft', 'Excerpt', 'Content', null, false, '2026-07-30T12:00:00+00:00'));
        self::assertFalse($draft->isPublished());
        self::assertNull($draft->getPublishedAt());
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
