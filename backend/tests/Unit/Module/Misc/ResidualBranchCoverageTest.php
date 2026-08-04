<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Training\Application\DTO\TrainingEnrollmentCheckoutResult;
use App\Module\Training\Application\DTO\TrainingSessionInput;
use App\Module\Training\Domain\Entity\Training;
use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\Domain\Entity\TrainingSession;
use App\Module\User\Application\DTO\RegisterUserInput;
use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class ResidualBranchCoverageTest extends TestCase
{
    public function testPromotionInputParsesEndDate(): void
    {
        $input = PromotionInput::fromArray([
            'name' => 'Promo',
            'slug' => 'promo',
            'discountType' => 'percent',
            'discountValue' => 10,
            'audienceKey' => 'all_users',
            'endsAt' => '2026-08-01',
        ]);

        self::assertInstanceOf(\DateTimeImmutable::class, $input->endsAt);
    }

    public function testTrainingSessionInputRejectsInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dates de session invalides.');
        TrainingSessionInput::fromArray(['startsAt' => 'invalid', 'endsAt' => 'invalid']);
    }

    public function testRegisterAndProfileValidationNoOpBranches(): void
    {
        $register = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'Password1',
            'confirmPassword' => 'Password1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '',
            'phoneNumber' => '',
            'gender' => 'femme',
        ]);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');
        $register->validateBirthDate($context);
        $register->validatePhoneNumber($context);

        $profile = UpdateProfileInput::fromArray([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'birthDate' => '2999-01-01',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('birthDate')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');
        $context2 = $this->createMock(ExecutionContextInterface::class);
        $context2->expects(self::once())->method('buildViolation')->with('La date de naissance ne peut pas etre dans le futur.')->willReturn($builder);
        $profile->validateBirthDate($context2);
    }

    public function testRegisterBirthDateValidationRejectsInvalidFormat(): void
    {
        $register = RegisterUserInput::fromArray([
            'email' => 'ada@example.com',
            'password' => 'Password1',
            'confirmPassword' => 'Password1',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'birthDate' => '2026-99-99',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('birthDate')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->with('La date de naissance est invalide.')->willReturn($builder);

        $register->validateBirthDate($context);
    }

    public function testTrainingEnrollmentCheckoutResultAndEntity(): void
    {
        $training = $this->createMock(Training::class);
        $session = new TrainingSession(
            $training,
            'remote',
            new \DateTimeImmutable('2026-08-01T09:00:00+00:00'),
            new \DateTimeImmutable('2026-08-01T17:00:00+00:00'),
            10,
        );
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $enrollment = new TrainingEnrollment($session, $user, 10000);

        $enrollment
            ->setStatus(TrainingEnrollment::STATUS_CONFIRMED)
            ->setPriceCents(12000)
            ->setScheduledStartsAt(new \DateTimeImmutable('2026-08-02T09:00:00+00:00'))
            ->setScheduledEndsAt(new \DateTimeImmutable('2026-08-02T17:00:00+00:00'))
            ->setPaidAt(new \DateTimeImmutable('2026-07-30T09:00:00+00:00'))
            ->setStripeSessionId('cs_1')
            ->setStripePaymentIntentId('pi_1');

        self::assertSame($session, $enrollment->getSession());
        self::assertSame($user, $enrollment->getUser());
        self::assertSame(12000, $enrollment->getPriceCents());
        self::assertSame('cs_1', $enrollment->getStripeSessionId());
        self::assertSame('pi_1', $enrollment->getStripePaymentIntentId());

        $result = new TrainingEnrollmentCheckoutResult($enrollment, true, 'https://checkout.example.com');
        self::assertSame($enrollment, $result->enrollment);
        self::assertTrue($result->created);
        self::assertSame('https://checkout.example.com', $result->checkoutUrl);
    }
}
