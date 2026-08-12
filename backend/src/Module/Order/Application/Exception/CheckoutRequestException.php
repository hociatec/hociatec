<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Exception;

use App\Shared\Application\Exception\AbstractApiProblemException;

final class CheckoutRequestException extends AbstractApiProblemException
{
    private function __construct(string $message, string $errorCode)
    {
        parent::__construct($message, 400, $message, $errorCode);
    }

    public static function missingCartShippingAddress(): self
    {
        return new self('Aucune adresse de livraison trouvée.', 'MISSING_SHIPPING_ADDRESS');
    }

    public static function invalidCartShippingAddress(): self
    {
        return new self('Adresse de livraison invalide.', 'INVALID_SHIPPING_ADDRESS');
    }

    public static function emptyCart(): self
    {
        return new self('Le panier est vide.', 'EMPTY_CART');
    }

    public static function invalidOrder(): self
    {
        return new self('Commande invalide.', 'INVALID_ORDER');
    }

    public static function orderCannotBePaid(): self
    {
        return new self('Cette commande ne peut pas être réglée.', 'ORDER_NOT_PAYABLE');
    }

    public static function orderHasNothingToPay(): self
    {
        return new self('Cette commande ne contient rien à régler.', 'ORDER_HAS_NOTHING_TO_PAY');
    }
}
