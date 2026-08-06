<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

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
        $page = $request->query->getInt('page', 1);
        if (1 > $page) {
            $page = 1;
        }

        $perPage = $request->query->getInt('perPage', $defaultPerPage);
        if (1 > $perPage) {
            $perPage = 1;
        } elseif ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

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
            'totalPages' => 1 > (int) ceil($total / $this->perPage) ? 1 : (int) ceil($total / $this->perPage),
        ];
    }
}
