<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\HttpFoundation\Request;

final readonly class Pagination
{
    public function __construct(
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request, int $defaultPerPage = 20, int $maxPerPage = 100): self
    {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = max(1, min($maxPerPage, $request->query->getInt('perPage', $defaultPerPage)));

        return new self($page, $perPage);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** @return array{page:int,perPage:int,total:int,totalPages:int} */
    public function metadata(int $total): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'total' => $total,
            'totalPages' => max(1, (int) ceil($total / $this->perPage)),
        ];
    }
}
