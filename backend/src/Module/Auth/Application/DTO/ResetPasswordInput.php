<?php

declare(strict_types=1);

namespace App\Module\Auth\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validatePasswords')]
final readonly class ResetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        #[Assert\Regex(
            pattern: '/^(?=.*[A-Z])(?=.*\d).{8,}$/',
            message: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.',
        )]
        public string $password,
        #[Assert\NotBlank]
        #[Assert\Length(max: 4096)]
        public string $confirmPassword,
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            is_string($payload['password'] ?? null) ? $payload['password'] : '',
            is_string($payload['confirmPassword'] ?? null) ? $payload['confirmPassword'] : '',
        );
    }

    public function validatePasswords(ExecutionContextInterface $context): void
    {
        if ($this->password !== $this->confirmPassword) {
            $context->buildViolation('Les mots de passe doivent être identiques.')
                ->atPath('confirmPassword')
                ->addViolation();
        }
    }
}
