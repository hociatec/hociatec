<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\TradeIn\Application\Port\TradeInPrivateFileStoragePort;
use App\Module\TradeIn\Application\Port\TradeInRequestRepositoryPort;
use App\Module\TradeIn\Application\Projection\TradeInFormatter;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerTradeInPortalService
{
    public function __construct(
        private TradeInRequestRepositoryPort $requests,
        private TradeInFormatter $formatter,
        private TradeInAccessPolicy $accessPolicy,
        private TradeInRequestWorkflow $workflow,
        private ?TradeInPrivateFileStoragePort $files = null,
    ) {
    }

    /**
     * @return array{items:list<array<string,mixed>>, total:int}
     */
    public function listForUser(User $user, int $limit, int $offset): array
    {
        $items = $this->requests->findByUser($user, $limit, $offset);

        return [
            'items' => array_map(fn (TradeInRequest $request): array => $this->formatter->format($request), $items),
            'total' => $this->requests->countByUser($user),
        ];
    }

    /**
     * @return array{status:string}|null
     */
    public function respondToOfferForUser(User $user, int $requestId, string $action): ?array
    {
        $request = $this->findAccessibleOfferRequest($user, $requestId);
        if (!$request instanceof TradeInRequest) {
            return null;
        }

        if (TradeInStatus::OFFER_SENT !== $request->getStatus() || null === $request->getOfferCents()) {
            throw new \DomainException('Aucune offre n’est disponible.');
        }

        $status = match ($action) {
            'accept' => TradeInStatus::ACCEPTED,
            'decline' => TradeInStatus::DECLINED,
            default => null,
        };
        if (null === $status) {
            throw new \InvalidArgumentException('Réponse invalide.');
        }

        $this->workflow->setStatus($request, $status);

        return ['status' => $status->value];
    }

    /**
     * @return array{content:string,filename:string}|null
     */
    public function downloadReceiptForUser(User $user, int $requestId): ?array
    {
        if (!$this->files instanceof TradeInPrivateFileStoragePort) {
            throw new \LogicException('Trade-in private file storage is not configured.');
        }

        $request = $this->findAccessibleReceiptRequest($user, $requestId);
        if (!$request instanceof TradeInRequest) {
            return null;
        }

        $receiptPath = $request->getReceiptPath();
        if (null === $receiptPath) {
            return null;
        }

        return [
            'content' => $this->files->read($receiptPath),
            'filename' => 'justificatif-reprise-'.$request->getReference().'.pdf',
        ];
    }

    private function findAccessibleOfferRequest(User $user, int $requestId): ?TradeInRequest
    {
        $request = $this->requests->find($requestId);

        return $request instanceof TradeInRequest && $this->accessPolicy->canRespondToOffer($user, $request) ? $request : null;
    }

    private function findAccessibleReceiptRequest(User $user, int $requestId): ?TradeInRequest
    {
        $request = $this->requests->find($requestId);

        return $request instanceof TradeInRequest && $this->accessPolicy->canDownloadReceipt($user, $request) ? $request : null;
    }
}
