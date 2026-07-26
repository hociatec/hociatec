<?php

declare(strict_types=1);

namespace App\Module\User\Controller\Address;

use App\Module\User\DTO\ShippingAddressInput;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\User\Service\ShippingAddressFormatter;
use App\Shared\Http\ApiResponse;
use App\Shared\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses', name: 'api_addresses_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressRepository $addresses,
        private readonly DtoValidator $dtoValidator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = \App\Shared\Http\JsonPayload::decode($request);

        $input = ShippingAddressInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        /** @var User $user */
        $user = $this->getUser();
        $address = new ShippingAddress($user, $input->name, $input->address, $input->postalCode, $input->city);
        $address
            ->setCompany($input->company)
            ->setCompanySiren($input->companySiren)
            ->setCompanyVatNumber($input->companyVatNumber)
            ->setPurchaseOrderNumber($input->purchaseOrderNumber);
        $this->addresses->save($address, true);

        // set default if requested or first address
        $isDefault = isset($payload['isDefault']) ? (bool) $payload['isDefault'] : false;
        if ($isDefault || null === $this->addresses->findDefaultForUser($user)) {
            $this->addresses->setDefault($user, $address);
        }

        return ApiResponse::created(['address' => ShippingAddressFormatter::toArray($address)]);
    }
}
