<?php

declare(strict_types=1);

namespace App\Module\User\DTO;

use App\Shared\Normalization\EmailNormalizer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateBirthDate')]
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
    public string $birthDate;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public string $phoneNumber;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['homme', 'femme', 'autre'], message: 'La valeur du champ sexe est invalide.')]
    public string $gender;

    #[Assert\Length(min: 8, max: 4096)]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
        message: 'Le mot de passe doit contenir au moins une majuscule et un chiffre.'
    )]
    public ?string $newPassword = null;

    #[Assert\Length(max: 4096)]
    public ?string $currentPassword = null;

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $self = new self();
        $self->firstName = trim((string) ($payload['firstName'] ?? ''));
        $self->lastName = trim((string) ($payload['lastName'] ?? ''));
        $self->email = EmailNormalizer::normalize((string) ($payload['email'] ?? ''));
        // address fields excluded
        $self->birthDate = trim((string) ($payload['birthDate'] ?? ''));
        $self->phoneNumber = trim((string) ($payload['phoneNumber'] ?? ''));
        $self->gender = trim((string) ($payload['gender'] ?? ''));

        $password = $payload['newPassword'] ?? $payload['password'] ?? null;
        if (is_string($password) && '' !== trim($password)) {
            $self->newPassword = $password;
        }

        $currentPassword = $payload['currentPassword'] ?? null;
        if (is_string($currentPassword) && '' !== trim($currentPassword)) {
            $self->currentPassword = $currentPassword;
        }

        return $self;
    }

    public function validateBirthDate(ExecutionContextInterface $context): void
    {
        $birthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->birthDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (
            !$birthDate instanceof \DateTimeImmutable
            || (false !== $dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            $context->buildViolation('La date de naissance est invalide.')
                ->atPath('birthDate')
                ->addViolation();

            return;
        }

        if ($birthDate > new \DateTimeImmutable('today')) {
            $context->buildViolation('La date de naissance ne peut pas etre dans le futur.')
                ->atPath('birthDate')
                ->addViolation();
        }
    }
}
