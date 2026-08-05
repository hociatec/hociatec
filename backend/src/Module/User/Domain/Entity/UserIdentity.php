<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity;

use App\Shared\Domain\Normalization\EmailNormalizer;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class UserIdentity
{
    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 50)]
    private string $firstName;

    #[ORM\Column(length: 50)]
    private string $lastName;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $birthDate;

    #[ORM\Column(length: 20)]
    private string $phoneNumber;

    #[ORM\Column(length: 10)]
    private string $gender;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        \DateTimeImmutable $birthDate,
        string $phoneNumber,
        string $gender,
    ) {
        $this->email = EmailNormalizer::normalize($email);
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->birthDate = $birthDate;
        $this->phoneNumber = $phoneNumber;
        $this->gender = $gender;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function changeEmail(string $email): void
    {
        $this->email = EmailNormalizer::normalize($email);
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function changeFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function changeLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function birthDate(): \DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function changeBirthDate(\DateTimeImmutable $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function phoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function changePhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function gender(): string
    {
        return $this->gender;
    }

    public function changeGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function fullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }
}
