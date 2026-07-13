<?php

declare(strict_types=1);

namespace App\Module\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
        message: 'Le mot de passe doit contenir au moins 8 caracteres, une majuscule et un chiffre.'
    )]
    public string $password;

    #[Assert\NotBlank]
    public string $confirmPassword;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Date]
    public string $birthDate;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public string $phoneNumber;

    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['homme', 'femme', 'autre'],
        message: 'La valeur du champ sexe est invalide.'
    )]
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
        $self->email = (string) ($payload['email'] ?? '');
        $self->password = (string) ($payload['password'] ?? '');
        $self->confirmPassword = (string) ($payload['confirmPassword'] ?? '');
        $self->firstName = (string) ($payload['firstName'] ?? '');
        $self->lastName = (string) ($payload['lastName'] ?? '');
        // address fields removed from registration; user will manage addresses later
        $self->birthDate = (string) ($payload['birthDate'] ?? '');
        $self->phoneNumber = (string) ($payload['phoneNumber'] ?? '');
        $self->gender = (string) ($payload['gender'] ?? '');

        return $self;
    }
}
