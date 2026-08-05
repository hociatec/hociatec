<?php

declare(strict_types=1);

namespace App\Module\BetaTest\UI\Http;

use App\Module\BetaTest\Domain\Entity\BugReportComment;

final readonly class BugReportCommentFormatter
{
    public function __construct()
    {
    }

    /** @return array<string, mixed> */
    public function format(BugReportComment $comment): array
    {
        $author = $comment->getAuthor();

        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'author' => [
                'id' => $author->getId(),
                'firstName' => $author->getFirstName(),
                'lastName' => $author->getLastName(),
                'email' => $author->getEmail(),
                'role' => $author->isAdmin() ? 'admin' : 'user',
            ],
        ];
    }
}
