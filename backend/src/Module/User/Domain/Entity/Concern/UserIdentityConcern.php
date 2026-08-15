<?php

declare(strict_types=1);

namespace App\Module\User\Domain\Entity\Concern;

trait UserIdentityConcern
{
    public function getEmail(): string
    {
        return $this->identity->email();
    }

    public function getUserIdentifier(): string
    {
        return $this->requireEmailIdentifier();
    }

    public function getFirstName(): string
    {
        return $this->identity->firstName();
    }

    public function setFirstName(string $firstName): self
    {
        $this->identity->changeFirstName($firstName);

        return $this;
    }

    public function getLastName(): string
    {
        return $this->identity->lastName();
    }

    public function setLastName(string $lastName): self
    {
        $this->identity->changeLastName($lastName);

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->identity->changeEmail($email);

        return $this;
    }

    public function getBirthDate(): \DateTimeImmutable
    {
        return $this->identity->birthDate();
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): self
    {
        $this->identity->changeBirthDate($birthDate);

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->identity->phoneNumber();
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->identity->changePhoneNumber($phoneNumber);

        return $this;
    }

    public function getGender(): string
    {
        return $this->identity->gender();
    }

    public function setGender(string $gender): self
    {
        $this->identity->changeGender($gender);

        return $this;
    }

    public function getFullName(): string
    {
        return $this->identity->fullName();
    }

    private function requireEmailIdentifier(): string
    {
        $email = $this->identity->email();
        if ('' === $email) {
            throw new \LogicException('A persisted user must have an email address.');
        }

        return $email;
    }
}
