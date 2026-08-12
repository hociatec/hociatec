<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Voucher\Controller;

use App\Module\Admin\Application\Voucher\DTO\VoucherInput;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Shared\Infrastructure\Http\ApiProblemResponse;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\InvalidJsonPayloadException;
use App\Shared\Infrastructure\Http\RequestPayloadMapper;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vouchers', name: 'api_admin_vouchers_create', methods: ['POST'])]
#[IsGranted('ROLE_VOUCHERS_MANAGER')]
final class CreateVoucherController extends AbstractController
{
    public function __construct(
        private readonly CreateVoucherHandler $createVoucher,
        private readonly DtoValidator $validator,
        private readonly VoucherFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);
        } catch (InvalidJsonPayloadException) {
            return ApiResponse::error('Payload invalide.', Response::HTTP_BAD_REQUEST);
        }

        $input = VoucherInput::fromArray($payload);
        $this->validator->validate($input);
        try {
            $voucher = $this->createVoucher->create($this->toPayload($input));
        } catch (\InvalidArgumentException $exception) {
            return ApiProblemResponse::fromThrowable($exception, 'Création du bon de réduction invalide.', Response::HTTP_BAD_REQUEST);
        }

        return ApiResponse::created([
            'voucher' => $this->formatter->formatVoucher($voucher),
        ], 'Le bon de réduction a bien été créé.');
    }

    /** @return array{name: string, code: string, description: string|null, discountType: string, discountValue: int, isActive: bool, startsAt: \DateTimeImmutable|null, endsAt: \DateTimeImmutable|null} */
    private function toPayload(VoucherInput $input): array
    {
        return ['name' => $input->name, 'code' => $input->code, 'description' => $input->description, 'discountType' => $input->discountType, 'discountValue' => $input->discountValue, 'isActive' => $input->isActive, 'startsAt' => RequestPayloadMapper::dateOrNull($input->startsAt), 'endsAt' => RequestPayloadMapper::dateOrNull($input->endsAt)];
    }
}
