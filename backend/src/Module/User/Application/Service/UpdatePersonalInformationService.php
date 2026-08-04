<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Domain\Entity\User;

final class UpdatePersonalInformationService
{
    public function update(User $user, UpdateProfileInput $input): void
    {
        $user
            ->setFirstName($input->firstName)
            ->setLastName($input->lastName)
            ->setBirthDate($this->parseBirthDate($input->birthDate))
            ->setPhoneNumber($input->phoneNumber)
            ->setGender($input->gender);
    }

    private function parseBirthDate(string $birthDate): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (
            !$date instanceof \DateTimeImmutable
            || (false !== $dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            throw InvalidBirthDateException::invalid();
        }

        if ($date > new \DateTimeImmutable('today')) {
            throw InvalidBirthDateException::inFuture();
        }

        return $date;
    }
}
