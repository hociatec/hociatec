<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller\Address;

use App\Module\User\Application\DTO\ShippingAddressInput;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\User\Application\Writer\ShippingAddressWriter;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses', name: 'api_addresses_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAddressController extends AbstractController
{
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly ShippingAddressWriter $writer,
        private readonly DtoValidator $dtoValidator,
        private readonly ShippingAddressFormatter $formatter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);

        $input = ShippingAddressInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        $user = $this->currentUser();
        $address = new ShippingAddress($user, $input->name, $input->address, $input->postalCode, $input->city);
        $address
            ->setType($input->type)
            ->setAddressComplement($input->addressComplement)
            ->setCompany($input->company)
            ->setCompanySiren($input->companySiren)
            ->setCompanyVatNumber($input->companyVatNumber);
        $isDefault = isset($payload['isDefault']) ? (bool) $payload['isDefault'] : false;
        $this->writer->saveWithDefaultPolicy($user, $address, $isDefault);

        return ApiResponse::createdItem('address', $this->formatter->toArray($address));
    }
}
