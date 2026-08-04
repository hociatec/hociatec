<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\Service;

use App\Module\Admin\Application\User\DTO\CustomerVoucherInput;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class CreateCustomerVoucherHandler
{
    public function __construct(
        private CreateVoucherHandler $createVoucher,
        private VoucherNotificationEmailService $notifications,
        private VoucherRepositoryPort $vouchers,
        private DoctrineUnitOfWork $unitOfWork,
    ) {
    }

    /**
     * @param array{
     *   name:string,
     *   code:string,
     *   description?:?string,
     *   discountType:string,
     *   discountValue:int,
     *   isActive?:bool,
     *   startsAt?:?\DateTimeImmutable,
     *   endsAt?:?\DateTimeImmutable
     * } $data
     */
    public function create(User $user, CustomerVoucherInput $input, array $data): CreatedCustomerVoucher
    {
        $voucher = $this->createVoucher->create($data);
        $emailSent = false;

        $voucher
            ->setRecipientUserId($user->getId())
            ->setRecipientEmail($user->getEmail());

        if ($input->sendEmail) {
            $this->notifications->sendCustomerVoucher($user, $voucher);
            $voucher->setSentAt(new \DateTimeImmutable());
            $emailSent = true;
        }

        $this->vouchers->save($voucher);
        $this->unitOfWork->commit();

        return new CreatedCustomerVoucher($voucher, $emailSent);
    }
}

final readonly class CreatedCustomerVoucher
{
    public function __construct(public Voucher $voucher, public bool $emailSent)
    {
    }
}
