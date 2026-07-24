<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\DTO\RegisterUserInput;
use App\Module\User\Entity\User;
use App\Module\User\Exception\InvalidBirthDateException;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AccountActivationEmailService $activationEmails,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function register(RegisterUserInput $input): User
    {
        if ($this->userRepository->existsByEmail($input->email)) {
            throw UserAlreadyExistsException::forEmail($input->email);
        }

        $birthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $input->birthDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (
            !$birthDate instanceof \DateTimeImmutable
            || (false !== $dateErrors && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        ) {
            throw InvalidBirthDateException::invalid();
        }

        if ($birthDate > new \DateTimeImmutable('today')) {
            throw InvalidBirthDateException::inFuture();
        }

        $user = new User(
            $input->email,
            $input->firstName,
            $input->lastName,
            $birthDate,
            $input->phoneNumber,
            $input->gender,
        );
        $hashedPassword = $this->passwordHasher->hashPassword($user, $input->password);
        $user->setPassword($hashedPassword);

        $token = VerificationTokenHasher::generateRawToken();
        $expiresAt = new \DateTimeImmutable('+24 hours');
        $user->setVerificationToken(VerificationTokenHasher::hash($token));
        $user->setVerificationTokenExpiresAt($expiresAt);
        $user->setIsVerified(false);

        try {
            return $this->entityManager->wrapInTransaction(function () use ($user, $token): User {
                $this->userRepository->save($user, true);
                $this->activationEmails->sendActivationEmail($user, $token);

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (!UserUniqueConstraintViolationDetector::isEmail($exception)) {
                throw $exception;
            }

            throw UserAlreadyExistsException::forEmail($input->email);
        }
    }
}
