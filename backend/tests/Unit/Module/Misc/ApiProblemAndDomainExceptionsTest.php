<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\Order\Application\Exception\CartCheckoutConflictException;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Rating\Application\Exception\ProductReviewException;
use App\Module\Training\Application\Exception\TrainingSessionUnavailableException;
use App\Module\User\Application\Exception\ActivationEmailDeliveryException;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use PHPUnit\Framework\TestCase;

final class ApiProblemAndDomainExceptionsTest extends TestCase
{
    public function testExposeExpectedMessagesAndStatusCodes(): void
    {
        $productForm = new ProductFormRequestException('Invalid product payload', 422);
        self::assertSame(422, $productForm->getStatusCode());
        self::assertSame('Invalid product payload', $productForm->getMessage());

        self::assertSame(404, (new OperationsResourceNotFoundException('missing'))->getStatusCode());
        self::assertSame(422, (new InvalidAppointmentSlotException('slot'))->getStatusCode());
        self::assertSame(409, (new CartCheckoutConflictException('conflict'))->getStatusCode());
        self::assertSame(404, (new CartCheckoutNotFoundException('missing'))->getStatusCode());
        self::assertSame(422, (new ProductReviewException('review'))->getStatusCode());
        self::assertSame(409, (new TrainingSessionUnavailableException('busy'))->getStatusCode());

        $activation = ActivationEmailDeliveryException::deliveryFailed(new \RuntimeException('smtp'));
        self::assertSame(503, $activation->getStatusCode());
        self::assertSame("L'e-mail d'activation n'a pas pu etre envoye.", $activation->getMessage());

        self::assertSame('La date de naissance est invalide.', InvalidBirthDateException::invalid()->getMessage());
        self::assertSame('La date de naissance ne peut pas etre dans le futur.', InvalidBirthDateException::inFuture()->getMessage());
        self::assertSame('Le mot de passe actuel est obligatoire pour cette modification.', InvalidCurrentPasswordException::missing()->getMessage());
        self::assertSame('Le mot de passe actuel est incorrect.', InvalidCurrentPasswordException::invalid()->getMessage());
        self::assertSame('Le nouveau mot de passe ne peut pas etre vide.', InvalidProfilePasswordException::empty()->getMessage());
        self::assertSame('Un utilisateur existe deja avec l\'adresse e-mail "ada@example.com".', UserAlreadyExistsException::forEmail('ada@example.com')->getMessage());
        self::assertSame(409, UserAlreadyExistsException::forEmail('ada@example.com')->getStatusCode());
    }
}
