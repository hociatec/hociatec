<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Application\Workflow\TradeInService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\RateLimited;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public/trade-ins', name: 'api_public_trade_ins_create', methods: ['POST'])]
#[RateLimited('public_api')]
final class CreatePublicTradeInController extends AbstractController
{
    public function __construct(private readonly TradeInService $service, private readonly DtoValidator $validator, private readonly ProductRepository $products)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->request->all();
        $input = TradeInInput::fromArray([] !== $payload ? $payload : \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request));
        $rib = $request->files->get('rib');
        if (!$rib instanceof UploadedFile) {
            return ApiResponse::error('Le RIB du demandeur doit être fourni au format PDF.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        /** @var User|null $user */
        $user = $this->getUser() instanceof User ? $this->getUser() : null;
        if (null !== $user) {
            $input = $input->withContact($user->getFirstName(), $user->getLastName(), $user->getEmail(), $user->getPhoneNumber());
        }
        $this->validator->validate($input, message: 'Formulaire de reprise invalide.');
        $product = null !== $input->catalogProductId ? $this->products->find($input->catalogProductId) : null;
        if (null !== $input->catalogProductId && (null === $product || !$product->isPublished())) {
            return ApiResponse::error('Produit de catalogue introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $tradeIn = $this->service->create($input, $user, $product, $rib);

        return ApiResponse::created(TradeInFormatter::format($tradeIn), 'Votre demande de reprise a bien été enregistrée.');
    }
}
