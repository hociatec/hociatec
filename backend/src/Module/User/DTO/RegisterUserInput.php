<?php

declare(strict_types=1);

namespace App\Module\User\DTO;

use App\Shared\Normalization\EmailNormalizer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateBirthDate')]
#[Assert\Callback('validatePhoneNumber')]
class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
        message: 'Le mot de passe doit contenir au moins 8 caracteres, une majuscule et un chiffre.'
    )]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(max: 4096)]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Les mots de passe ne correspondent pas.')]
    public string $confirmPassword;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^[^\p{C}]+$/u', message: 'Le prenom contient des caracteres invalides.')]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^[^\p{C}]+$/u', message: 'Le nom contient des caracteres invalides.')]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 10)]
    public string $birthDate;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 20)]
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
        $self->email = EmailNormalizer::normalize(self::stringValue($payload, 'email'));
        $self->password = self::stringValue($payload, 'password');
        $self->confirmPassword = self::stringValue($payload, 'confirmPassword');
        $self->firstName = trim(self::stringValue($payload, 'firstName'));
        $self->lastName = trim(self::stringValue($payload, 'lastName'));
        $self->birthDate = trim(self::stringValue($payload, 'birthDate'));
        $self->phoneNumber = trim(self::stringValue($payload, 'phoneNumber'));
        $self->gender = mb_strtolower(trim(self::stringValue($payload, 'gender')));

        return $self;
    }

    public function validateBirthDate(ExecutionContextInterface $context): void
    {
        if ('' === $this->birthDate) {
            return;
        }

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

    public function validatePhoneNumber(ExecutionContextInterface $context): void
    {
        if ('' === $this->phoneNumber) {
            return;
        }

        $normalizedPhone = preg_replace('/[\s().-]+/', '', $this->phoneNumber);
        if (!is_string($normalizedPhone) || 1 !== preg_match('/^\+?[0-9]{6,15}$/', $normalizedPhone)) {
            $context->buildViolation('Le numero de telephone est invalide.')
                ->atPath('phoneNumber')
                ->addViolation();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function stringValue(array $payload, string $key): string
    {
        $value = $payload[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
