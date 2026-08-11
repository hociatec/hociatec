<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Controller;

use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Application\Workflow\CustomerTradeInPortalService;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use App\Module\TradeIn\UI\Controller\DownloadMyTradeInReceiptController;
use App\Module\TradeIn\UI\Controller\ListMyTradeInsController;
use App\Module\TradeIn\UI\Controller\RespondToTradeInOfferController;
use App\Tests\Unit\Module\TradeIn\TradeInIntegrationTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TradeInUserControllersTest extends TradeInIntegrationTestCase
{
    public function testUserControllersCoverListDownloadAndOfferResponses(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $other = $this->user([], 'grace@example.com');
        $request = $this->tradeInRequest($user)->setOffer(12000, new \DateTimeImmutable('+1 week'))->setStatus(TradeInStatus::OFFER_SENT);
        $request->setReceiptPath('var/private/trade-ins/receipt.pdf');
        $submitted = $this->tradeInRequest($user, 'TR-2');
        $foreign = $this->tradeInRequest($other, 'TR-3')->setOffer(9000)->setStatus(TradeInStatus::OFFER_SENT);
        $em->persist($user);
        $em->persist($other);
        $em->persist($request);
        $em->persist($submitted);
        $em->persist($foreign);
        $em->flush();
        file_put_contents($this->projectDir().'/var/private/trade-ins/receipt.pdf', '%PDF-receipt');

        $repository = $this->tradeInRepository($em);
        $portal = new CustomerTradeInPortalService(
            $repository,
            new TradeInFormatter(),
            new TradeInAccessPolicy(),
            $this->tradeInService($this->mockEntityManager(self::any())),
            new TradeInPrivateFileStorage($this->projectDir()),
        );
        $list = new ListMyTradeInsController($portal);
        $list->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_OK, $list()->getStatusCode());

        $download = new DownloadMyTradeInReceiptController($portal, new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        $download->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_OK, $download((int) $request->getId())->getStatusCode());

        $respond = new RespondToTradeInOfferController($portal);
        $respond->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_NOT_FOUND, $respond((int) $foreign->getId(), 'accept')->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $respond((int) $submitted->getId(), 'accept')->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $respond((int) $request->getId(), 'bogus')->getStatusCode());
        self::assertSame(Response::HTTP_OK, $respond((int) $request->getId(), 'accept')->getStatusCode());
    }

    public function testDownloadRejectsMissingOrForeignReceipt(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $foreign = $this->user([], 'foreign@example.com');
        $request = $this->tradeInRequest($foreign);
        $ownedWithoutReceipt = $this->tradeInRequest($user, 'TR-NO-RECEIPT');
        $em->persist($user);
        $em->persist($foreign);
        $em->persist($request);
        $em->persist($ownedWithoutReceipt);
        $em->flush();

        $download = new DownloadMyTradeInReceiptController(
            new CustomerTradeInPortalService(
                $this->tradeInRepository($em),
                new TradeInFormatter(),
                new TradeInAccessPolicy(),
                $this->tradeInService($this->mockEntityManager(self::any())),
                new TradeInPrivateFileStorage($this->projectDir()),
            ),
            new \App\Shared\Infrastructure\Http\AttachmentResponseFactory(),
        );
        $download->setContainer($this->controllerContainer($user));

        try {
            $download((int) $ownedWithoutReceipt->getId());
            self::fail('Expected missing receipt exception.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception) {
            self::assertSame('Justificatif indisponible.', $exception->getMessage());
        }

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $download((int) $request->getId());
    }
}
