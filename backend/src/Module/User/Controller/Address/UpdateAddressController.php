<?php

declare(strict_types=1);

namespace App\Module\User\Controller\Address;

use App\Module\User\DTO\ShippingAddressInput;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Module\User\Service\ShippingAddressFormatter;
use App\Shared\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/addresses/{id}', name: 'api_addresses_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
#[IsGranted('ROLE_USER')]
class UpdateAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressRepository $addresses,
        private readonly ValidatorInterface $validator,
    ) {}

    public function __invoke(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $address = $this->addresses->findOneForUser($id, $user);
        if ($address === null) {
            return ApiResponse::error('Adresse introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return ApiResponse::error('Payload JSON invalide.');
        }

        $input = ShippingAddressInput::fromArray($payload);
        $violations = $this->validator->validate($input);
        if ($violations->count() > 0) {
            return ApiResponse::error('Validation des donnees echouee.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $this->formatViolations($violations));
        }

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

        $this->addresses->save($address, true);

        return ApiResponse::success(['address' => ShippingAddressFormatter::toArray($address)]);
    }

    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }
        return $errors;
    }
}
