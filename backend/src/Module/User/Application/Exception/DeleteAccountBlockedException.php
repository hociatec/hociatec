<?php

declare(strict_types=1);

namespace App\Module\User\Application\Exception;

final class DeleteAccountBlockedException extends \RuntimeException
{
    public static function activeOrders(): self
    {
        return new self('Le compte ne peut pas etre supprime tant que des commandes sont encore actives.');
    }
}
