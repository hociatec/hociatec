<?php

declare(strict_types=1);

namespace App\Module\News\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'news_comments')]
#[ORM\Index(name: 'IDX_NEWS_COMMENTS_ARTICLE', columns: ['article_id', 'created_at'])]
class NewsComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NewsArticle::class)]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private NewsArticle $article;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(NewsArticle $article, User $author, string $content)
    {
        $this->article = $article;
        $this->author = $author;
        $this->content = trim($content);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): NewsArticle
    {
        return $this->article;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
