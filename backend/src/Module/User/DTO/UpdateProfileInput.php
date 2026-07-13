<?php

declare(strict_types=1);

namespace App\Module\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    // Address fields removed from profile edition; managed via dedicated endpoints

    #[Assert\NotBlank]
    #[Assert\Date]
    public string $birthDate;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public string $phoneNumber;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['homme', 'femme', 'autre'], message: 'La valeur du champ sexe est invalide.')]
    public string $gender;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $self = new self();
        $self->firstName = (string) ($payload['firstName'] ?? '');
        $self->lastName = (string) ($payload['lastName'] ?? '');
        $self->email = (string) ($payload['email'] ?? '');
        // address fields excluded
        $self->birthDate = (string) ($payload['birthDate'] ?? '');
        $self->phoneNumber = (string) ($payload['phoneNumber'] ?? '');
        $self->gender = (string) ($payload['gender'] ?? '');

        return $self;
    }
}
