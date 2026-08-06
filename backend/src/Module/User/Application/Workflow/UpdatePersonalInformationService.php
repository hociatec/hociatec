<?php

declare(strict_types=1);

namespace App\Module\User\Application\Workflow;

use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\DateTime\DateTimeParser;

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
        $date = DateTimeParser::fromFormat('!Y-m-d', $birthDate);
        if (!$date instanceof \DateTimeImmutable) {
            throw InvalidBirthDateException::invalid();
        }

        if ($date > new \DateTimeImmutable('today')) {
            throw InvalidBirthDateException::inFuture();
        }

        return $date;
    }
}
