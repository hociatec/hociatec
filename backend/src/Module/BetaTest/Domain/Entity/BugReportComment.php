<?php

declare(strict_types=1);

namespace App\Module\BetaTest\Domain\Entity;

use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'beta_bug_report_comments')]
#[ORM\Index(name: 'idx_bug_report_comment_report', columns: ['bug_report_id'])]
class BugReportComment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BugReport::class)]
    #[ORM\JoinColumn(name: 'bug_report_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BugReport $bugReport;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(BugReport $bugReport, User $author, string $content)
    {
        $this->bugReport = $bugReport;
        $this->author = $author;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBugReport(): BugReport
    {
        return $this->bugReport;
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
