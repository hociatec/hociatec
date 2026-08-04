<?php

declare(strict_types=1);

namespace App\Module\News\Application\Writer;

use App\Module\News\Domain\Entity\NewsArticle;
use App\Module\News\Domain\Entity\NewsComment;
use App\Module\News\Domain\Exception\NewsOperationException;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class NewsCommentWriter
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function create(NewsArticle $article, User $author, string $content): NewsComment
    {
        $content = trim($content);
        if (mb_strlen($content) < 3 || mb_strlen($content) > 1200) {
            throw new \InvalidArgumentException('Le commentaire doit contenir entre 3 et 1200 caractères.');
        }

        $comment = new NewsComment($article, $author, $content);

        try {
            $this->persistence->persist($comment);
            $this->persistence->commit();
        } catch (\RuntimeException $exception) {
            throw NewsOperationException::failed('Impossible de publier le commentaire.', $exception);
        }

        return $comment;
    }
}
