<?php

declare(strict_types=1);

namespace App\Module\User\UI\Controller\Address;

use App\Module\User\Application\DTO\ShippingAddressInput;
use App\Module\User\Application\Projection\ShippingAddressFormatter;
use App\Module\User\Application\Writer\ShippingAddressWriter;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Application\Port\ShippingAddressRepositoryPort;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/addresses/{id}', name: 'api_addresses_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
class UpdateAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressRepositoryPort $addresses,
        private readonly ShippingAddressWriter $writer,
        private readonly DtoValidator $dtoValidator,
        private readonly ShippingAddressFormatter $formatter,
    ) {
    }

    public function __invoke(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $address = $this->addresses->findOneForUser($id, $user);
        if (null === $address) {
            return ApiResponse::error('Adresse introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $payload = \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request);

        $input = ShippingAddressInput::fromArray($payload);
        $this->dtoValidator->validate($input);

        // Update fields
        $address
            ->setName($input->name)
            ->setAddress($input->address)
            ->setPostalCode($input->postalCode)
            ->setCity($input->city)
            ->setCompany($input->company)
            ->setCompanySiren($input->companySiren)
            ->setCompanyVatNumber($input->companyVatNumber)
            ->setPurchaseOrderNumber($input->purchaseOrderNumber);

        $this->writer->save($address);

        return ApiResponse::successItem('address', $this->formatter->toArray($address));
    }
}
