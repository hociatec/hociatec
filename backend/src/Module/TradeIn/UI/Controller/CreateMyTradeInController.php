<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Http\JsonPayload;
use App\Infrastructure\Validation\DtoValidator;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Service\TradeInFormatter;
use App\Module\TradeIn\Application\Service\TradeInService;
use App\Module\User\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trade-ins', name: 'api_trade_ins_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final class CreateMyTradeInController extends AbstractController
{
    public function __construct(private readonly TradeInService $service, private readonly DtoValidator $validator, private readonly ProductRepository $products)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = $request->request->all();
        $input = TradeInInput::fromArray([] !== $payload ? $payload : JsonPayload::decode($request))->withContact($user->getFirstName(), $user->getLastName(), $user->getEmail(), $user->getPhoneNumber());
        $rib = $request->files->get('rib');
        if (!$rib instanceof UploadedFile) {
            return ApiResponse::error('Le RIB du demandeur doit être fourni au format PDF.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->validator->validate($input, message: 'Formulaire de reprise invalide.');
        $product = null !== $input->catalogProductId ? $this->products->find($input->catalogProductId) : null;
        if (null !== $input->catalogProductId && (null === $product || !$product->isPublished())) {
            return ApiResponse::error('Produit de catalogue introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::created(TradeInFormatter::format($this->service->create($input, $user, $product, $rib)), 'Votre demande de reprise a bien été enregistrée.');
    }
}
