<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Module\User\Infrastructure\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<UserInterface>
 */
final readonly class EmailUserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $users)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findOneByEmailInsensitive($identifier);
        if (null === $user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return new SymfonySecurityUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $id = method_exists($user, 'getId') ? $user->getId() : null;
        if (!\is_int($id)) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $refreshed = $this->users->find($id);
        if (null === $refreshed) {
            throw new UserNotFoundException(sprintf('User "%d" not found.', $id));
        }

        return new SymfonySecurityUser($refreshed);
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, SymfonySecurityUser::class, true)
            || is_a($class, \App\Module\User\Domain\Entity\User::class, true);
    }
}
