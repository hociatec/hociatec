<?php

declare(strict_types=1);

namespace App\Module\News\Application\Writer;

use App\Module\News\Application\DTO\NewsArticleInput;
use App\Module\News\Application\Message\NewsArticlePublishedEmailMessage;
use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use App\Shared\Application\UnitOfWork;

final readonly class NewsArticleWriter
{
    public function __construct(
        private UnitOfWork $persistence,
        private UserRepositoryPort $users,
        private AsyncMessageDispatcher $bus,
    ) {
    }

    public function create(NewsArticleInput $input): NewsArticle
    {
        $article = new NewsArticle($input->title, $input->slug, $input->excerpt, $input->content);
        $this->apply($article, $input);

        try {
            $this->persistence->persist($article);
            $this->persistence->commit();

            if ($article->isPublished()) {
                $this->dispatchPublishedEmails($article);
            }
        } catch (\RuntimeException $exception) {
            throw NewsOperationException::failed('Impossible de créer l’actualité.', $exception);
        }

        return $article;
    }

    public function update(NewsArticle $article, NewsArticleInput $input): NewsArticle
    {
        $wasPublished = $article->isPublished();
        $this->apply($article, $input);

        try {
            $this->persistence->commit();

            if (!$wasPublished && $article->isPublished()) {
                $this->dispatchPublishedEmails($article);
            }
        } catch (\RuntimeException $exception) {
            throw NewsOperationException::failed('Impossible de mettre à jour l’actualité.', $exception);
        }

        return $article;
    }

    public function delete(object $entity): void
    {
        try {
            $this->persistence->remove($entity);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw NewsOperationException::failed('Impossible de supprimer l’actualité.', $exception);
        }
    }

    public function sendPublishedEmails(NewsArticle $article): void
    {
        if (!$article->isPublished()) {
            throw new \InvalidArgumentException('Seule une actualité publiée peut être envoyée par e-mail.');
        }

        try {
            $this->dispatchPublishedEmails($article);
        } catch (\RuntimeException $exception) {
            throw NewsOperationException::failed('Impossible de planifier l’envoi de l’actualité.', $exception);
        }
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

        try {
            return new \DateTimeImmutable($input->publishedAt);
        } catch (\DateMalformedStringException $exception) {
            throw new \InvalidArgumentException('Date de publication invalide.', 0, $exception);
        }
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
