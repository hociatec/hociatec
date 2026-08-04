<?php

declare(strict_types=1);

namespace App\Module\News\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'news_articles')]
#[ORM\UniqueConstraint(name: 'UNIQ_NEWS_ARTICLES_SLUG', columns: ['slug'])]
#[ORM\Index(name: 'IDX_NEWS_ARTICLES_PUBLISHED', columns: ['is_published', 'published_at'])]
#[ORM\HasLifecycleCallbacks]
class NewsArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(length: 190)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $excerpt;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'is_published', type: 'boolean', options: ['default' => true])]
    private bool $isPublished = true;

    #[ORM\Column(name: 'published_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $title, string $slug, string $excerpt, string $content)
    {
        $this->title = trim($title);
        $this->slug = trim($slug);
        $this->excerpt = trim($excerpt);
        $this->content = trim($content);
        $now = new \DateTimeImmutable();
        $this->publishedAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): self
    {
        $this->excerpt = trim($excerpt);

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $category = null !== $category ? trim($category) : null;
        $this->category = '' !== $category ? $category : null;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setPublished(bool $published): self
    {
        $this->isPublished = $published;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
