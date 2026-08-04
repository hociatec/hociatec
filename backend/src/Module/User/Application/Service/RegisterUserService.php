<?php

declare(strict_types=1);

namespace App\Module\User\Application\Service;

use App\Module\BetaTest\Application\Service\BetaTesterProfileService;
use App\Module\Outbox\Application\Outbox;
use App\Module\User\Application\DTO\RegisterUserInput;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Outbox $outbox,
        private readonly UserPersistence $persistence,
        private readonly BetaTesterProfileService $betaProfiles,
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
            return $this->persistence->transactional(function () use ($user, $token, $input): User {
                $this->persistence->save($user);
                $this->persistence->flush();
                if (null !== $input->betaProfile) {
                    $this->betaProfiles->create($user, $input->betaProfile);
                    $this->persistence->flush();
                }
                $this->outbox->record('user.activation.'.$user->getId().'.'.$user->getVerificationToken(), 'user.activation_email_requested', [
                    'userId' => $user->getId(),
                    'token' => $token,
                ]);

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
