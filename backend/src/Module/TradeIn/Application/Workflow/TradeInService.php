<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\TradeIn\Application\Calculator\TradeInEstimator;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Application\Factory\TradeInNumberGenerator;
use App\Module\TradeIn\Application\Port\TradeInPrivateFileStoragePort;
use App\Module\TradeIn\Application\Port\TradeInPersistencePort;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\ValueObject\TradeInEstimate;
use App\Module\User\Domain\Entity\User;

final readonly class TradeInService
{
    public function __construct(
        private TradeInPersistencePort $persistence,
        private TradeInEstimator $estimator,
        private TradeInNumberGenerator $numbers,
        private TradeInNotificationEmailService $notifications,
        private TradeInPrivateFileStoragePort $files,
        private TradeInStatusWorkflow $workflow,
    ) {
    }

    public function create(TradeInInput $input, ?User $user, ?Product $product, ?object $rib = null): TradeInRequest
    {
        $estimate = $this->estimator->estimate($input, $product?->getPriceCents());
        $request = new TradeInRequest(
            $this->numbers->generate(),
            $user,
            $input->applicant(),
            $input->productSnapshot($product?->getId(), $product?->getName()),
            new TradeInEstimate($estimate['minCents'], $estimate['maxCents'], null, null),
            new \DateTimeImmutable(),
        );
        if (null !== $rib) {
            $stored = $this->files->storeRib($rib);
            $request->setRib($stored['path'], $stored['originalName'], $stored['size'], $stored['sha256']);
        }
        $this->persistence->save($request);
        $this->persistence->commit();
        $this->notifications->sendCreated($request);

        return $request;
    }

    public function setStatus(TradeInRequest $request, TradeInStatus $status): void
    {
        $previous = $request->getStatus();
        if (!$this->canTransition($request->getStatus(), $status)) {
            throw new \InvalidArgumentException(sprintf('Cette transition est impossible : « %s » vers « %s ».', $this->statusLabel($request->getStatus()), $this->statusLabel($status)));
        }
        $request->setStatus($status);
        $this->persistence->save($request);
        $this->persistence->commit();
        if ($previous !== $status) {
            $this->notifications->sendStatusChanged($request);
        }
    }

    public function setOffer(TradeInRequest $request, int $offerCents, ?\DateTimeImmutable $expiresAt, ?string $note): void
    {
        if (!$this->canTransition($request->getStatus(), TradeInStatus::OFFER_SENT)) {
            throw new \InvalidArgumentException(sprintf('Cette transition est impossible : « %s » vers « %s ».', $this->statusLabel($request->getStatus()), $this->statusLabel(TradeInStatus::OFFER_SENT)));
        }
        $request->setOffer($offerCents, $expiresAt)->setAdminNote($note)->setStatus(TradeInStatus::OFFER_SENT);
        $this->persistence->save($request);
        $this->persistence->commit();
        $this->notifications->sendStatusChanged($request);
    }

    private function canTransition(TradeInStatus $from, TradeInStatus $to): bool
    {
        return ($from === $to) || $this->workflow->canTransitionTo($from, $to);
    }

    private function statusLabel(TradeInStatus $status): string
    {
        return $status->label();
    }
}
