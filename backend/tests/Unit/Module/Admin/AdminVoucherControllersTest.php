<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin;

use App\Module\Admin\UI\Voucher\Controller\CreateVoucherController;
use App\Module\Admin\UI\Voucher\Controller\DeleteVoucherController;
use App\Module\Admin\UI\Voucher\Controller\GetVoucherController;
use App\Module\Admin\UI\Voucher\Controller\ListVouchersController;
use App\Module\Admin\UI\Voucher\Controller\UpdateVoucherController;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Module\Voucher\Infrastructure\Repository\VoucherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminVoucherControllersTest extends AdminModuleIntegrationTestCase
{
    public function testAdminVoucherControllers(): void
    {
        $em = $this->entityManager();
        $repository = new VoucherRepository($this->registry($em));
        $validator = $this->validator(5);
        $formatter = new VoucherFormatter();

        $create = new CreateVoucherController($this->createVoucherHandler($em), $validator, $formatter);
        self::assertSame(Response::HTTP_BAD_REQUEST, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $create($this->jsonRequest($this->voucherPayload(['discountValue' => 101])))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $create($this->jsonRequest($this->voucherPayload(['code' => 'BADDATE', 'startsAt' => 'bad', 'endsAt' => ''])))->getStatusCode());
        $created = $create($this->jsonRequest($this->voucherPayload()));
        self::assertSame(Response::HTTP_CREATED, $created->getStatusCode());
        $voucherId = (int) $this->payload($created)['data']['voucher']['id'];

        self::assertSame(Response::HTTP_OK, (new ListVouchersController($repository, $formatter))(Request::create('/?page=1&perPage=5'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new GetVoucherController($repository, $formatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new GetVoucherController($repository, $formatter))($voucherId)->getStatusCode());

        $update = new UpdateVoucherController($repository, $this->updateVoucherHandler($em), $validator, $formatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $update(999, $this->jsonRequest($this->voucherPayload(), 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $update($voucherId, Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $update($voucherId, $this->jsonRequest($this->voucherPayload(['startsAt' => '2026-08-12', 'endsAt' => '2026-08-01']), 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $update($voucherId, $this->jsonRequest($this->voucherPayload(['name' => 'Voucher updated', 'startsAt' => 'bad', 'endsAt' => '']), 'PUT'))->getStatusCode());

        $delete = new DeleteVoucherController($repository, $this->deleteVoucherHandler($em));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete($voucherId)->getStatusCode());
    }
}
