<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class UpdateProfileService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly DoctrineUnitOfWork $unitOfWork,
        private readonly UpdatePersonalInformationService $personalInformation,
        private readonly ChangeProfileEmailService $emailChanger,
        private readonly ChangeProfilePasswordService $passwordChanger,
    ) {
    }

    /**
     * @throws InvalidBirthDateException
     * @throws InvalidCurrentPasswordException
     * @throws InvalidProfilePasswordException
     * @throws UserAlreadyExistsException
     */
    public function update(User $user, UpdateProfileInput $input): User
    {
        $userId = $user->getId();
        if (null === $userId) {
            throw new \LogicException('Cannot update the profile of a non-persisted user.');
        }

        $this->personalInformation->update($user, $input);
        $this->emailChanger->change($user, $userId, $input->email, $input->currentPassword);
        $this->passwordChanger->change($user, $input->newPassword, $input->currentPassword);

        try {
            $this->userRepository->save($user);
            $this->unitOfWork->commit();
        } catch (UniqueConstraintViolationException $exception) {
            if (!UserUniqueConstraintViolationDetector::isEmail($exception)) {
                throw $exception;
            }

            throw new UserAlreadyExistsException('Cet email est deja utilise par un autre compte.');
        }

        return $user;
    }
}
