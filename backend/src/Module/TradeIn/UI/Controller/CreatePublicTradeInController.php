<?php

declare(strict_types=1);

namespace App\Module\TradeIn\UI\Controller;

use App\Module\Catalog\Application\Port\ProductRepositoryPort;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Application\Workflow\TradeInRequestWorkflow;
use App\Module\TradeIn\UI\Http\PublicTradeInRateLimiter;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiResponse;
use App\Shared\Infrastructure\Http\AuthenticatedDomainUserTrait;
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
    use AuthenticatedDomainUserTrait;

    public function __construct(
        private readonly TradeInRequestWorkflow $service,
        private readonly DtoValidator $validator,
        private readonly ProductRepositoryPort $products,
        private readonly TradeInFormatter $formatter,
        private readonly PublicTradeInRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->request->all();
        $input = TradeInInput::fromArray([] !== $payload ? $payload : \App\Shared\Infrastructure\Http\JsonRequestInput::payload($request));
        $rib = $request->files->get('rib');
        if (!$rib instanceof UploadedFile) {
            return ApiResponse::error('Le RIB du demandeur doit être fourni au format PDF.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $user = $this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface ? $this->currentUserOrNull() : null;
        if (null !== $user) {
            $input = $input->withContact($user->getFirstName(), $user->getLastName(), $user->getEmail(), $user->getPhoneNumber());
        }
        $this->validator->validate($input, message: 'Formulaire de reprise invalide.');
        if (!$this->rateLimiter->isAccepted($request, $input->email)) {
            return ApiResponse::error('Trop de demandes de reprise. Veuillez réessayer plus tard.', JsonResponse::HTTP_TOO_MANY_REQUESTS);
        }
        $product = null !== $input->catalogProductId ? $this->products->find($input->catalogProductId) : null;
        if (null !== $input->catalogProductId && (null === $product || !$product->isPublished())) {
            return ApiResponse::error('Produit de catalogue introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $tradeIn = $this->service->create($input, $user, $product, $rib);

        return ApiResponse::created($this->formatter->format($tradeIn), 'Votre demande de reprise a bien été enregistrée.');
    }

    private function currentUserOrNull(): ?User
    {
        try {
            return $this->currentUser();
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException) {
            return null;
        }
    }
}
