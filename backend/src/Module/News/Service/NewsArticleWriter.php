<?php

declare(strict_types=1);

namespace App\Module\News\Service;

use App\Module\News\DTO\NewsArticleInput;
use App\Module\News\Entity\NewsArticle;
use App\Module\News\Message\NewsArticlePublishedEmailMessage;
use App\Module\User\Repository\UserRepository;
use App\Shared\Persistence\DoctrinePersistence;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class NewsArticleWriter
{
    public function __construct(
        private DoctrinePersistence $persistence,
        private UserRepository $users,
        private MessageBusInterface $bus,
    ) {
    }

    public function create(NewsArticleInput $input): NewsArticle
    {
        $article = new NewsArticle($input->title, $input->slug, $input->excerpt, $input->content);
        $this->apply($article, $input);
        $this->persistence->persist($article);
        $this->persistence->flush();

        if ($article->isPublished()) {
            $this->dispatchPublishedEmails($article);
        }

        return $article;
    }

    public function update(NewsArticle $article, NewsArticleInput $input): NewsArticle
    {
        $wasPublished = $article->isPublished();
        $this->apply($article, $input);
        $this->persistence->flush();

        if (!$wasPublished && $article->isPublished()) {
            $this->dispatchPublishedEmails($article);
        }

        return $article;
    }

    public function delete(object $entity): void
    {
        $this->persistence->remove($entity);
        $this->persistence->flush();
    }

    public function sendPublishedEmails(NewsArticle $article): void
    {
        if (!$article->isPublished()) {
            throw new \InvalidArgumentException('Seule une actualité publiée peut être envoyée par e-mail.');
        }

        $this->dispatchPublishedEmails($article);
    }

    private function apply(NewsArticle $article, NewsArticleInput $input): void
    {
        if ('' === $input->title || '' === $input->slug || '' === $input->excerpt || '' === $input->content) {
            throw new \InvalidArgumentException('Titre, slug, résumé et contenu sont obligatoires.');
        }

        $article
            ->setTitle($input->title)
            ->setSlug($input->slug)
            ->setExcerpt($input->excerpt)
            ->setContent($input->content)
            ->setCategory($input->category)
            ->setPublished($input->isPublished)
            ->setPublishedAt($this->publishedAt($input));
    }

    private function publishedAt(NewsArticleInput $input): ?\DateTimeImmutable
    {
        if (!$input->isPublished) {
            return null;
        }

        if (null === $input->publishedAt || '' === $input->publishedAt) {
            return new \DateTimeImmutable();
        }

        return new \DateTimeImmutable($input->publishedAt);
    }

    private function dispatchPublishedEmails(NewsArticle $article): void
    {
        foreach ($this->users->findNewsEmailSubscribers() as $user) {
            $userId = $user->getId();
            if (null === $userId) {
                continue;
            }

            $this->bus->dispatch(new NewsArticlePublishedEmailMessage(
                $userId,
                $article->getTitle(),
                $article->getExcerpt(),
                $article->getSlug(),
            ));
        }
    }
}
