<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Appointment\Application\DTO\CreateAppointmentInput;
use App\Module\Appointment\Application\DTO\UpdateAppointmentStatusInput;
use App\Module\Appointment\Application\DTO\WorkingDayData;
use App\Module\BetaTest\Application\DTO\BetaProfileInput;
use App\Module\BetaTest\Application\Service\BetaProfileChoices;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Notification\Application\DTO\NotificationReadStateInput;
use App\Module\Order\Application\DTO\CartCheckoutResult;
use App\Module\Order\Application\DTO\DeliveryInput;
use App\Module\Order\Application\DTO\RefundCreateData;
use App\Module\Order\Application\DTO\RefundProcessData;
use App\Module\Order\Application\DTO\RefundUpdateData;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Promotion\Application\DTO\PromotionInput;
use App\Module\Quote\Application\DTO\QuoteItemAddition;
use App\Module\Quote\Application\DTO\QuoteItemPayload;
use App\Module\Quote\Application\DTO\QuotePayload;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\DTO\SupportUpdateData;
use App\Module\Training\Application\DTO\TrainingInput;
use App\Module\Training\Application\DTO\TrainingSessionInput;
use App\Module\User\Application\DTO\RegisterUserInput;
use App\Module\User\Application\DTO\ShippingAddressInput;
use App\Module\User\Application\DTO\UpdateProfileInput;
use App\Module\User\Domain\Entity\User;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class SecondaryDtoAndValueObjectCoverageTest extends TestCase
{
    public function testValueObjectsAndSimpleDtos(): void
    {
        $url = Url::fromString('https://example.com/path');
        self::assertSame('https://example.com/path', $url->value());

        $money = Money::fromCents(1000);
        self::assertSame(1500, $money->add(Money::fromCents(500))->cents());
        self::assertSame(200, $money->subtract(Money::fromCents(800))->cents());
        self::assertSame(0, $money->subtract(Money::fromCents(5000))->cents());
        self::assertTrue($money->equals(Money::fromCents(1000)));

        $appointment = CreateAppointmentInput::fromArray(['prestationId' => '4', 'startAt' => ' 2026-08-01T10:00:00+00:00 ']);
        self::assertSame(4, $appointment->prestationId);
        self::assertSame('2026-08-01T10:00:00+00:00', $appointment->startAt);

        $appointmentStatus = UpdateAppointmentStatusInput::fromArray(['status' => ' confirmed ']);
        self::assertSame('confirmed', $appointmentStatus->status);

        $workingDay = new WorkingDayData(1, true, '08:00', '18:00', [['start' => '12:00', 'end' => '13:00']]);
        self::assertSame(1, $workingDay->dayOfWeek);
        self::assertTrue($workingDay->isWorkingDay);
        self::assertSame('08:00', $workingDay->startTime);

        $readState = NotificationReadStateInput::fromArray([
            'seenKeys' => [' a ', '', 'b'],
            'dismissedKey' => ' x ',
            'dismissedKeys' => [' y ', '', 'z'],
            'seenSignature' => ' sig ',
        ]);
        self::assertSame(['a', 'b'], $readState->seenKeys);
        self::assertSame('x', $readState->dismissedKey);
        self::assertSame(['y', 'z'], $readState->dismissedKeys);
        self::assertSame('sig', $readState->seenSignature);

        $refundCreate = new RefundCreateData(1, 2000, 'reason', 'notes', 3, 'EUR');
        self::assertSame(1, $refundCreate->orderId);
        $refundProcess = new RefundProcessData('REMBOURSER', 'pi_1');
        self::assertSame('pi_1', $refundProcess->paymentIntentId);
        $refundUpdate = new RefundUpdateData('approved', 're_1', 'notes');
        self::assertSame('approved', $refundUpdate->status);

        $supportCreate = new SupportCreateData(1, 'Subject', 'other', 'message', 'note', 4);
        self::assertSame('Subject', $supportCreate->subject);
        $supportReply = new SupportReplyData('message', 'subject', 'resolved');
        self::assertSame('resolved', $supportReply->status);
        $supportUpdate = new SupportUpdateData('in_progress', 'notes', 'subject');
        self::assertSame('subject', $supportUpdate->subject);
    }

    public function testPromotionQuoteTrainingAndUserDtos(): void
    {
        $promotion = PromotionInput::fromArray([
            'name' => ' Promo ',
            'slug' => ' promo-1 ',
            'discountType' => 'percent',
            'discountValue' => '10',
            'audienceKey' => 'all_users',
            'criteria' => ['minimumCartTotalCents' => 1000],
            'description' => ' Desc ',
            'isActive' => false,
            'startsAt' => '2026-07-01',
            'endsAt' => 'invalid-date',
        ]);
        self::assertSame('Promo', $promotion->name);
        self::assertSame('promo-1', $promotion->slug);
        self::assertFalse($promotion->isActive);
        self::assertInstanceOf(\DateTimeImmutable::class, $promotion->startsAt);
        self::assertNull($promotion->endsAt);

        $quoteItemAddition = QuoteItemAddition::fromArray(['name' => ' Service ', 'unitPriceCents' => '-10', 'description' => ' Desc ', 'unit' => 'h', 'quantity' => '0', 'vatRate' => '20', 'vatRateBps' => '-1', 'discountCents' => '-3']);
        self::assertSame('Service', $quoteItemAddition->name);
        self::assertSame(0, $quoteItemAddition->unitPriceCents);
        self::assertSame(1, $quoteItemAddition->quantity);
        self::assertSame(0, $quoteItemAddition->vatRateBps);
        self::assertSame(0, $quoteItemAddition->discountCents);

        $quoteItemPayload = QuoteItemPayload::fromArray(['name' => ' Item ', 'productId' => '1', 'serviceId' => '2', 'unitPriceCents' => '1000', 'description' => ' Desc ', 'unit' => 'u', 'quantity' => '0', 'vatRateBps' => '-1', 'discountCents' => '-5', 'type' => 'product']);
        self::assertSame('Item', $quoteItemPayload->name);
        self::assertSame(1, $quoteItemPayload->productId);
        self::assertSame(1, $quoteItemPayload->quantity);

        $quotePayload = QuotePayload::fromArray([
            'customer' => ['name' => 'Ada'],
            'status' => ' sent ',
            'discountCents' => '100',
            'shippingCents' => '200',
            'conditions' => ' Net ',
            'validFrom' => '2026-07-01',
            'validUntil' => '2026-07-31',
            'items' => [['name' => 'Item', 'quantity' => 2]],
        ]);
        self::assertSame(['name' => 'Ada'], $quotePayload->customer);
        self::assertSame('sent', $quotePayload->status);
        self::assertSame(100, $quotePayload->discount->cents());
        self::assertSame(200, $quotePayload->shipping->cents());
        self::assertCount(1, $quotePayload->items);

        $training = TrainingInput::fromArray([
            'title' => ' Training ',
            'slug' => ' training ',
            'shortDescription' => ' Short ',
            'objective' => ' Obj ',
            'audience' => ' Devs ',
            'category' => ' Web ',
            'durationMinutes' => '90',
            'priceCents' => '-100',
            'availableFormats' => [' onsite ', '', 'remote'],
            'roadmap' => [' step1 ', '', 'step2'],
            'isActive' => false,
        ]);
        self::assertSame('Training', $training->title);
        self::assertSame(0, $training->priceCents);
        self::assertSame(['onsite', 'remote'], $training->availableFormats);
        self::assertSame(['step1', 'step2'], $training->roadmap);
        self::assertFalse($training->isActive);

        $session = TrainingSessionInput::fromArray([
            'trainingId' => '5',
            'startsAt' => '2026-08-01T09:00:00+00:00',
            'endsAt' => '2026-08-02T18:00:00+00:00',
            'dailyStartTime' => '09:00',
            'dailyEndTime' => '17:00',
            'includeWeekends' => false,
            'format' => 'remote',
            'capacity' => '0',
            'location' => ' Paris ',
            'meetingUrl' => ' https://meet.example.com ',
            'status' => ' open ',
        ]);
        self::assertSame(5, $session->trainingId);
        self::assertSame(1, $session->capacity);
        self::assertFalse($session->includeWeekends);
        self::assertSame('remote', $session->format);
        self::assertSame('Paris', $session->location);

        $shipping = ShippingAddressInput::fromArray([
            'name' => 'Ada',
            'address' => '1 rue',
            'postalCode' => '75001',
            'city' => 'Paris',
            'company' => ' OpenAI ',
            'companySiren' => ' ',
            'companyVatNumber' => ' FR123 ',
            'purchaseOrderNumber' => ' PO-1 ',
        ]);
        self::assertSame('Ada', $shipping->name);
        self::assertSame('OpenAI', $shipping->company);
        self::assertNull($shipping->companySiren);
        self::assertSame('FR123', $shipping->companyVatNumber);

        $updateProfile = UpdateProfileInput::fromArray([
            'firstName' => ' Ada ',
            'lastName' => ' Lovelace ',
            'email' => ' ADA@EXAMPLE.COM ',
            'birthDate' => '1990-01-01',
            'phoneNumber' => ' 0102030405 ',
            'gender' => 'femme',
            'password' => 'Password1',
            'currentPassword' => 'Current1',
        ]);
        self::assertSame('Ada', $updateProfile->firstName);
        self::assertSame('ada@example.com', $updateProfile->email);
        self::assertSame('Password1', $updateProfile->newPassword);
        self::assertSame('Current1', $updateProfile->currentPassword);
    }

    public function testRegisterUserAndBetaProfileValidationCallbacks(): void
    {
        self::assertArrayHasKey('availability', BetaProfileChoices::groups());
        self::assertContains('weekdays', BetaProfileChoices::values('availability'));
        self::assertSame(['nvda'], BetaProfileChoices::normalizeList(['none', 'nvda'], 'assistiveTools'));
        self::assertSame(['chrome', 'firefox'], BetaProfileChoices::parseStoredList('chrome,firefox', 'browsers'));
        self::assertSame([], BetaProfileChoices::parseStoredList(' ', 'browsers'));
        self::assertSame('a,b', BetaProfileChoices::serializeList(['a', 'b']));

        $beta = BetaProfileInput::fromArray([
            'availability' => ['weekdays', 'invalid'],
            'motivation' => ' Help ',
            'testingExperience' => ['regular'],
            'bugDescriptionAbility' => ['steps'],
            'technicalKnowledge' => ['web'],
            'assistiveTools' => ['none', 'nvda'],
            'devices' => ['windows'],
            'browsers' => ['chrome'],
            'testingTypes' => ['bugs'],
            'betaConsent' => true,
        ]);
        self::assertSame(['weekdays'], $beta->availability);
        self::assertSame('Help', $beta->motivation);
        self::assertSame('regular', $beta->testingExperience);
        self::assertSame('steps', $beta->bugDescriptionAbility);
        self::assertSame('web', $beta->technicalKnowledge);
        self::assertSame(['nvda'], $beta->assistiveTools);

        $register = RegisterUserInput::fromArray([
            'email' => ' ADA@EXAMPLE.COM ',
            'password' => 'Password1',
            'confirmPassword' => 'Password1',
            'firstName' => ' Ada ',
            'lastName' => ' Lovelace ',
            'birthDate' => '2999-01-01',
            'phoneNumber' => 'bad-phone',
            'gender' => 'FEMME',
            'isBetaTester' => true,
            'availability' => ['weekdays'],
            'motivation' => 'Help',
            'testingExperience' => ['regular'],
            'bugDescriptionAbility' => ['steps'],
            'technicalKnowledge' => ['web'],
            'assistiveTools' => ['nvda'],
            'devices' => ['windows'],
            'browsers' => ['chrome'],
            'testingTypes' => ['bugs'],
            'betaConsent' => true,
        ]);
        self::assertSame('ada@example.com', $register->email);
        self::assertSame('femme', $register->gender);
        self::assertInstanceOf(BetaProfileInput::class, $register->betaProfile);

        $builder1 = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder1->expects(self::once())->method('atPath')->with('birthDate')->willReturnSelf();
        $builder1->expects(self::once())->method('addViolation');
        $context1 = $this->createMock(ExecutionContextInterface::class);
        $context1->expects(self::once())->method('buildViolation')->with('La date de naissance ne peut pas etre dans le futur.')->willReturn($builder1);
        $register->validateBirthDate($context1);

        $builder2 = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder2->expects(self::once())->method('atPath')->with('phoneNumber')->willReturnSelf();
        $builder2->expects(self::once())->method('addViolation');
        $context2 = $this->createMock(ExecutionContextInterface::class);
        $context2->expects(self::once())->method('buildViolation')->with('Le numero de telephone est invalide.')->willReturn($builder2);
        $register->validatePhoneNumber($context2);

        $profile = UpdateProfileInput::fromArray([
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@example.com',
            'birthDate' => 'invalid',
            'phoneNumber' => '0102030405',
            'gender' => 'femme',
        ]);
        $builder3 = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder3->expects(self::once())->method('atPath')->with('birthDate')->willReturnSelf();
        $builder3->expects(self::once())->method('addViolation');
        $context3 = $this->createMock(ExecutionContextInterface::class);
        $context3->expects(self::once())->method('buildViolation')->with('La date de naissance est invalide.')->willReturn($builder3);
        $profile->validateBirthDate($context3);
    }

    public function testCartCheckoutResultAndDeliveryInput(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'femme');
        $order = new \App\Module\Order\Domain\Entity\Order('ORD-1', $user);
        $existing = CartCheckoutResult::existingOrder($order);
        self::assertSame($order, $existing->order);
        self::assertNull($existing->checkout);

        $checkout = new OrderCheckoutSession('tok', $user, 'carttok', 1, 'stripe_1', 'https://checkout.example.com');
        $redirect = CartCheckoutResult::redirect($checkout);
        self::assertNull($redirect->order);
        self::assertSame($checkout, $redirect->checkout);

        $delivery = DeliveryInput::fromArray([
            'status' => ' shipped ',
            'carrier' => ' DHL ',
            'trackingNumber' => ' TRK ',
            'trackingUrl' => 'https://track.example.com',
            'estimatedAt' => ' 2026-08-10 ',
        ]);
        self::assertSame([
            'status' => 'shipped',
            'carrier' => 'DHL',
            'trackingNumber' => 'TRK',
            'trackingUrl' => 'https://track.example.com',
            'estimatedAt' => '2026-08-10',
        ], $delivery->toPayload());
    }
}
