<?php

declare(strict_types=1);

namespace App\Module\News\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'news_article_views')]
#[ORM\UniqueConstraint(name: 'UNIQ_NEWS_ARTICLE_VIEW_IP', columns: ['article_id', 'ip_hash'])]
class NewsArticleView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NewsArticle::class)]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private NewsArticle $article;

    #[ORM\Column(name: 'ip_hash', length: 64)]
    private string $ipHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $firstViewedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastViewedAt;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $viewsCount = 1;

    public function __construct(NewsArticle $article, string $ipHash)
    {
        $this->article = $article;
        $this->ipHash = $ipHash;
        $now = new \DateTimeImmutable();
        $this->firstViewedAt = $now;
        $this->lastViewedAt = $now;
    }

    public function markViewed(): void
    {
        ++$this->viewsCount;
        $this->lastViewedAt = new \DateTimeImmutable();
    }
}
