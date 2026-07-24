<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\Entity\User;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Repository\UserRepository;
use App\Shared\Normalization\EmailNormalizer;

final readonly class ChangeProfileEmailService
{
    public function __construct(
        private UserRepository $userRepository,
        private ProfileCurrentPasswordVerifier $currentPasswordVerifier,
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function change(User $user, int $userId, string $email, ?string $currentPassword): void
    {
        $normalizedEmail = EmailNormalizer::normalize($email);
        if (!$this->hasEmailChanged($user, $normalizedEmail)) {
            return;
        }

        $this->currentPasswordVerifier->verify($user, $currentPassword);

        if ($this->userRepository->existsByEmailExcludingUser($normalizedEmail, $userId)) {
            throw new UserAlreadyExistsException('Cet email est deja utilise par un autre compte.');
        }

        $user->setEmail($normalizedEmail);
    }

    private function hasEmailChanged(User $user, string $newEmail): bool
    {
        return 0 !== strcasecmp($user->getEmail(), $newEmail);
    }
}
