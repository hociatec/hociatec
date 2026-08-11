<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Service;

use App\Module\TradeIn\Application\DTO\TradeInClosureInput;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Tests\Unit\Module\TradeIn\TradeInIntegrationTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

final class TradeInWorkflowCompletionTest extends TradeInIntegrationTestCase
{
    public function testTradeInServiceCoversCreateOfferTransitionsAndInvalidStatus(): void
    {
        $service = $this->tradeInService($this->mockEntityManager(self::any()));
        $request = $service->create(TradeInInput::fromArray($this->payload()), null, $this->product(), $this->pdfUpload());
        self::assertNotNull($request->getRibPath());
        $service->setStatus($request, TradeInStatus::UNDER_REVIEW);
        $service->setOffer($request, 15000, new \DateTimeImmutable('+1 week'), 'Offre');
        $service->setStatus($request, TradeInStatus::DECLINED);
        $service->setStatus($request, TradeInStatus::DECLINED);

        foreach (TradeInStatus::cases() as $status) {
            $invalid = $this->tradeInRequest(null)->setStatus($status);
            try {
                $service->setStatus($invalid, TradeInStatus::SUBMITTED === $status ? TradeInStatus::COMPLETED : TradeInStatus::SUBMITTED);
                if (TradeInStatus::SUBMITTED !== $status) {
                    self::fail('Expected invalid transition for '.$status->value);
                }
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('Cette transition est impossible', $exception->getMessage());
            }
        }
    }

    public function testNotificationsCoverAllStatusLabelsOffersSkipAndFailures(): void
    {
        $mailer = $this->createMock(\App\Shared\Application\Mail\EmailSender::class);
        $mailer->expects(self::exactly(10))->method('send')->with(self::isInstanceOf(Email::class));
        $service = $this->notificationService($mailer);
        foreach (TradeInStatus::cases() as $status) {
            $request = $this->tradeInRequest(null)->setStatus($status);
            if (TradeInStatus::OFFER_SENT === $status) {
                $request->setOffer(12345, new \DateTimeImmutable('+1 week'));
            }
            $service->sendStatusChanged($request);
        }

        $skipMailer = $this->createMock(\App\Shared\Application\Mail\EmailSender::class);
        $skipMailer->expects(self::never())->method('send');
        $skipUser = $this->user();
        $this->notificationService($skipMailer)->sendStatusChanged($this->tradeInRequest($skipUser));

        $failingMailer = $this->createMock(\App\Shared\Application\Mail\EmailSender::class);
        $failingMailer->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');
        $this->notificationService($failingMailer, $logger)->sendCreated($this->tradeInRequest(null));
    }

    public function testClosureServiceValidatesStoresReceiptCompletesAndCreatesVoucher(): void
    {
        $service = $this->closureService();
        $submitted = $this->tradeInRequest(null);
        try {
            $service->close($submitted, new TradeInClosureInput(1000, 'cash', 'pending', null, null));
            self::fail('Expected inspected status.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('inspect', $exception->getMessage());
        }

        $request = $this->tradeInRequest(null)->setStatus(TradeInStatus::INSPECTED);
        $service->close($request, new TradeInClosureInput(1000, 'cash', 'paid', 'TX-1', 'Note'));
        self::assertSame(TradeInStatus::COMPLETED, $request->getStatus());
        self::assertSame('cash', $request->getPaymentMethod());
        self::assertNotNull($request->getReceiptPath());

        $em = $this->entityManager();
        $service = $this->closureService($em);
        $user = $this->user();
        $this->setId($user, 99);
        $em->persist($user);
        $em->flush();
        $storeCredit = $this->tradeInRequest($user)->setStatus(TradeInStatus::COMPLETED);
        $service->close($storeCredit, new TradeInClosureInput(2000, 'store_credit', 'pending', null, null));
        self::assertSame('paid', $storeCredit->getPaymentStatus());
        self::assertNotNull($storeCredit->getVoucherCode());
    }

    public function testClosureServiceRejectsInvalidAmountAndAnonymousStoreCredit(): void
    {
        $service = $this->closureService();
        $request = $this->tradeInRequest(null)->setStatus(TradeInStatus::INSPECTED);
        try {
            $service->close($request, new TradeInClosureInput(0, 'cash', 'paid', null, null));
            self::fail('Expected invalid amount.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('montant final', $exception->getMessage());
        }

        try {
            $service->close($request, new TradeInClosureInput(1000, 'store_credit', 'pending', null, null));
            self::fail('Expected anonymous store credit rejection.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('compte Hociatec', $exception->getMessage());
        }
    }
}
