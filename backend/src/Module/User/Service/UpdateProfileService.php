<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\DTO\UpdateProfileInput;
use App\Module\User\Entity\User;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UpdateProfileService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function update(User $user, UpdateProfileInput $input, ?string $newPassword = null): User
    {
        if ($user->getId() !== null && $this->hasEmailChanged($user, $input->email)) {
            if ($this->userRepository->existsByEmailExcludingUser($input->email, $user->getId())) {
                throw new UserAlreadyExistsException('Cet email est deja utilise par un autre compte.');
            }
        }

        $user
            ->setFirstName($input->firstName)
            ->setLastName($input->lastName)
            ->setEmail($input->email)
            ->setBirthDate(new DateTimeImmutable($input->birthDate))
            ->setPhoneNumber($input->phoneNumber)
            ->setGender($input->gender);

        if ($newPassword !== null && $newPassword !== '') {
            $this->assertPasswordInterface($user);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $this->userRepository->save($user, true);

        return $user;
    }

    private function hasEmailChanged(User $user, string $newEmail): bool
    {
        return strcasecmp($user->getEmail(), $newEmail) !== 0;
    }

    private function assertPasswordInterface(User $user): void
    {
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw new \RuntimeException('User does not support password updates.');
        }
    }
}
