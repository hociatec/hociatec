<?php

declare(strict_types=1);

namespace App\Module\User\Controller\Address;

use App\Module\User\DTO\ShippingAddressInput;
use App\Module\User\Entity\ShippingAddress;
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

#[Route('/api/addresses', name: 'api_addresses_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressRepository $addresses,
        private readonly ValidatorInterface $validator,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = (array) json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return ApiResponse::error('Invalid JSON payload.');
        }

        $input = ShippingAddressInput::fromArray($payload);
        $violations = $this->validator->validate($input);
        if ($violations->count() > 0) {
            return ApiResponse::error('Validation failed.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $this->formatViolations($violations));
        }

        /** @var User $user */
        $user = $this->getUser();
        $address = new ShippingAddress($user, $input->name, $input->address, $input->postalCode, $input->city);
        $this->addresses->save($address, true);

        // set default if requested or first address
        $isDefault = isset($payload['isDefault']) ? (bool) $payload['isDefault'] : false;
        if ($isDefault || $this->addresses->findDefaultForUser($user) === null) {
            $this->addresses->setDefault($user, $address);
        }

        return ApiResponse::created(['address' => ShippingAddressFormatter::toArray($address)]);
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
