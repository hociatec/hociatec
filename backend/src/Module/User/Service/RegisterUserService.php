<?php

declare(strict_types=1);

namespace App\Module\User\Service;

use App\Module\User\DTO\RegisterUserInput;
use App\Module\User\Entity\User;
use App\Module\User\Exception\UserAlreadyExistsException;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Shared\Http\OvhRoundcubeMailer;
use DateTimeImmutable;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly ShippingAddressRepository $addresses,
        private readonly EmailTemplateRenderer $emailTemplates,
    ) {
    }

    public function register(RegisterUserInput $input): User
    {
        if ($this->userRepository->existsByEmail($input->email)) {
            throw UserAlreadyExistsException::forEmail($input->email);
        }

        $birthDate = new DateTimeImmutable($input->birthDate);

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

        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable('+24 hours');
        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt($expiresAt);
        $user->setIsVerified(false);

        $this->userRepository->save($user, true);

        $frontendUrl = $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173';
        $verifyLink = rtrim($frontendUrl, '/') . '/activation/' . $token;
        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';

        $content = $this->emailTemplates->renderScenario('user_account_activation', [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'activation_url' => $verifyLink,
            'activation_expires_in' => '24 heures',
            'app_frontend_url' => rtrim((string) $frontendUrl, '/'),
        ], [
            'subject' => 'Activez votre compte Hociatec',
            'html' => '<p>Bonjour {{first_name}},</p><p>Merci pour votre inscription. Pour activer votre compte, cliquez sur le lien ci-dessous, valide {{activation_expires_in}}.</p><p><a href="{{activation_url}}">Activer mon compte</a></p><p>Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail.</p>',
            'text' => "Bonjour {{first_name}},\n\nMerci pour votre inscription. Pour activer votre compte, ouvrez le lien ci-dessous dans les {{activation_expires_in}} :\n{{activation_url}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.",
        ]);

        try {
            $this->ovhRoundcubeMailer->send(
                $user->getEmail(),
                $content['subject'],
                $content['text']
            );
        } catch (\Throwable) {
            try {
                $email = (new Email())
                    ->from(new Address($from, 'Hociatec'))
                    ->to(new Address($user->getEmail(), $user->getFullName()))
                    ->subject($content['subject'])
                    ->html($content['html'])
                    ->text($content['text']);

                $this->mailer->send($email);
            } catch (\Throwable) {
                // do not block registration if email fails; user can request a new email later
            }
        }

        return $user;
    }
}
