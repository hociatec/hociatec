<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin;

use App\Module\Admin\UI\TradeIn\Controller\CloseTradeInController;
use App\Module\Admin\UI\TradeIn\Controller\DeleteTradeInController;
use App\Module\Admin\UI\TradeIn\Controller\DownloadTradeInDocumentController;
use App\Module\Admin\UI\TradeIn\Controller\ListTradeInsController;
use App\Module\Admin\UI\TradeIn\Controller\SetTradeInOfferController;
use App\Module\Admin\UI\TradeIn\Controller\ShowTradeInController;
use App\Module\Admin\UI\TradeIn\Controller\UpdateTradeInStatusController;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Infrastructure\Persistence\TradeInPersistence;
use App\Module\TradeIn\Infrastructure\Repository\TradeInRequestRepository;
use App\Module\TradeIn\Infrastructure\Storage\TradeInPrivateFileStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AdminTradeInControllersTest extends AdminModuleIntegrationTestCase
{
    public function testAdminTradeInControllers(): void
    {
        $em = $this->entityManager();
        $user = $this->user();
        $submitted = $this->tradeIn($user, 'TR-ADM-1');
        $underReview = $this->tradeIn($user, 'TR-ADM-2')->setStatus(TradeInStatus::UNDER_REVIEW);
        $accepted = $this->tradeIn($user, 'TR-ADM-3')->setStatus(TradeInStatus::ACCEPTED);
        $inspected = $this->tradeIn($user, 'TR-ADM-4')->setStatus(TradeInStatus::INSPECTED);
        $inspected->setRib('var/private/trade-ins/rib.pdf', 'rib.pdf', 4, hash('sha256', 'rib'));
        foreach ([$user, $submitted, $underReview, $accepted, $inspected] as $entity) {
            $em->persist($entity);
        }
        $em->flush();
        file_put_contents($this->projectDir().'/var/private/trade-ins/rib.pdf', 'rib');

        $repository = new TradeInRequestRepository($this->registry($em));
        $service = $this->tradeInService($em);
        $validator = $this->validator(8);
        $formatter = new TradeInFormatter();

        self::assertSame(Response::HTTP_OK, (new ListTradeInsController($repository, $formatter))(Request::create('/?q=TR-ADM&status=submitted'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new ShowTradeInController($repository, $formatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ShowTradeInController($repository, $formatter))((int) $submitted->getId())->getStatusCode());

        $status = new UpdateTradeInStatusController($repository, $service, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $status(999, $this->jsonRequest(['status' => TradeInStatus::UNDER_REVIEW->value], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $status((int) $submitted->getId(), $this->jsonRequest(['status' => TradeInStatus::UNDER_REVIEW->value], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $status((int) $submitted->getId(), $this->jsonRequest(['status' => 'bad'], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $status((int) $underReview->getId(), $this->jsonRequest(['status' => TradeInStatus::COMPLETED->value], 'PUT'))->getStatusCode());

        $offer = new SetTradeInOfferController($repository, $service, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $offer(999, $this->jsonRequest(['offerCents' => 1000], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $offer((int) $underReview->getId(), $this->jsonRequest(['offerCents' => 1500, 'offerExpiresAt' => 'bad'], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $offer((int) $underReview->getId(), $this->jsonRequest(['offerCents' => 1500, 'offerExpiresAt' => '2026-08-12', 'adminNote' => 'Note'], 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $offer((int) $accepted->getId(), $this->jsonRequest(['offerCents' => 1500], 'PUT'))->getStatusCode());

        $download = new DownloadTradeInDocumentController($repository, new TradeInPrivateFileStorage($this->projectDir()), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        self::assertSame(Response::HTTP_OK, $download((int) $inspected->getId(), 'rib')->getStatusCode());
        try {
            $download(999, 'rib');
            self::fail('Expected missing trade-in document exception.');
        } catch (NotFoundHttpException) {
            self::assertTrue(true);
        }
        try {
            $download((int) $inspected->getId(), 'receipt');
            self::fail('Expected missing receipt exception.');
        } catch (NotFoundHttpException) {
            self::assertTrue(true);
        }

        $closure = new CloseTradeInController($repository, $this->closureService($em), $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $closure(999, $this->jsonRequest(['finalOfferCents' => 1000, 'paymentMethod' => 'cash', 'paymentStatus' => 'paid'], 'POST'))->getStatusCode());
        self::assertSame(Response::HTTP_CONFLICT, $closure((int) $submitted->getId(), $this->jsonRequest(['finalOfferCents' => 1000, 'paymentMethod' => 'cash', 'paymentStatus' => 'paid'], 'POST'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $closure((int) $inspected->getId(), $this->jsonRequest(['finalOfferCents' => 1000, 'paymentMethod' => 'cash', 'paymentStatus' => 'paid', 'transactionReference' => 'TX'], 'POST'))->getStatusCode());

        $delete = new DeleteTradeInController($repository, new \App\Module\Admin\Application\TradeIn\Handler\DeleteTradeInRequestHandler(
            $repository,
            new TradeInPersistence($em),
        ));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete((int) $submitted->getId())->getStatusCode());
    }
}
