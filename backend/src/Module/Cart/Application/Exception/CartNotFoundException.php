<?php

declare(strict_types=1);

namespace App\Module\Cart\Application\Exception;

use App\Shared\Application\Exception\ApiProblemException;

final class CartNotFoundException extends \InvalidArgumentException implements ApiProblemException
{
    public function __construct(string $message = 'Panier introuvable.')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 404;
    }

    public function publicMessage(): string
    {
        return $this->getMessage();
    }

    public function errorCode(): string
    {
        return 'CART_NOT_FOUND';
    }

    public function details(): array
    {
        return [];
    }
}
