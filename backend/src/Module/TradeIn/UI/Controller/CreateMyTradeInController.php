<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Application\Workflow\TradeInService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Validation\DtoValidator;
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
    public function __construct(
        private readonly TradeInService $service,
        private readonly DtoValidator $validator,
        private readonly ProductRepositoryPort $products,
        private readonly TradeInFormatter $formatter,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = $request->request->all();
        $input = TradeInInput::fromArray([] !== $payload ? $payload : \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request))->withContact($user->getFirstName(), $user->getLastName(), $user->getEmail(), $user->getPhoneNumber());
        $rib = $request->files->get('rib');
        if (!$rib instanceof UploadedFile) {
            return ApiResponse::error('Le RIB du demandeur doit être fourni au format PDF.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $this->validator->validate($input, message: 'Formulaire de reprise invalide.');
        $product = null !== $input->catalogProductId ? $this->products->find($input->catalogProductId) : null;
        if (null !== $input->catalogProductId && (null === $product || !$product->isPublished())) {
            return ApiResponse::error('Produit de catalogue introuvable.', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::created($this->formatter->format($this->service->create($input, $user, $product, $rib)), 'Votre demande de reprise a bien été enregistrée.');
    }
}
