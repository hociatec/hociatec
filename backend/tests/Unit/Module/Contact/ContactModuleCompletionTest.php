<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Contact;

use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Contact\Application\Notification\ContactAcknowledgementSender;
use App\Module\Contact\Application\Notification\ContactNotificationSender;
use App\Module\Contact\Application\Workflow\ContactFormSubmissionService;
use App\Module\Contact\UI\Controller\ContactController;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Shared\Application\Exception\MailDeliveryException;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactModuleCompletionTest extends TestCase
{
    public function testContactControllerReturnsSuccessAndDeliveryFailure(): void
    {
        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::exactly(2))->method('send')->with(self::isInstanceOf(Email::class));
        $controller = new ContactController($this->submission($mailer), $this->validator(1));

        self::assertSame(Response::HTTP_OK, $controller(Request::create('/', 'POST', [], [], [], [], json_encode($this->payload(), JSON_THROW_ON_ERROR)))->getStatusCode());

        $failingMailer = $this->createMock(EmailSender::class);
        $failingMailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $failingController = new ContactController($this->submission($failingMailer), $this->validator(1));

        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $failingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->payload(), JSON_THROW_ON_ERROR)))->getStatusCode());
    }

    public function testSubmissionWrapsNotificationErrorsAndIgnoresAcknowledgementErrors(): void
    {
        $notificationFailure = $this->createMock(EmailSender::class);
        $notificationFailure->expects(self::once())->method('send')->willThrowException(new \RuntimeException('smtp down'));

        try {
            $this->submission($notificationFailure)->submit(new ContactInput('Ada', 'ada@example.com', 'Sujet', 'Bonjour'));
            self::fail('Expected mail delivery exception.');
        } catch (MailDeliveryException $exception) {
            self::assertStringContainsString('contact_admin_notification', $exception->getMessage());
        }

        $typedFailure = MailDeliveryException::failed('typed_failure', new \RuntimeException('smtp down'));
        $typedFailureMailer = $this->createMock(EmailSender::class);
        $typedFailureMailer->expects(self::once())->method('send')->willThrowException($typedFailure);

        try {
            $this->submission($typedFailureMailer)->submit(new ContactInput('Ada', 'ada@example.com', 'Sujet', 'Bonjour'));
            self::fail('Expected typed mail delivery exception.');
        } catch (MailDeliveryException $exception) {
            self::assertSame($typedFailure, $exception);
        }

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::exactly(2))->method('send')->willReturnCallback(static function (): void {
            static $calls = 0;
            ++$calls;
            if (2 === $calls) {
                throw new \RuntimeException('ack down');
            }
        });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $this->submission($mailer, $logger)->submit(new ContactInput('Ada', 'ada@example.com', 'Sujet', 'Bonjour'));
    }

    private function submission(EmailSender $mailer, ?LoggerInterface $logger = null): ContactFormSubmissionService
    {
        $renderer = new EmailTemplateRenderer($this->createMock(EmailTemplateRepository::class));

        return new ContactFormSubmissionService(
            new ContactNotificationSender($renderer, $mailer, 'noreply@example.com', 'contact@example.com'),
            new ContactAcknowledgementSender($renderer, $mailer, 'noreply@example.com'),
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    private function validator(int $calls): DtoValidator
    {
        $symfonyValidator = $this->createMock(ValidatorInterface::class);
        $symfonyValidator->expects(self::exactly($calls))->method('validate')->willReturn(new ConstraintViolationList());

        return new DtoValidator($symfonyValidator, new ConstraintViolationFormatter());
    }

    /** @return array<string, string> */
    private function payload(): array
    {
        return ['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Sujet', 'message' => 'Bonjour'];
    }
}
