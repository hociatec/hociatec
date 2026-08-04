<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Service;

use App\Module\Catalog\Domain\Entity\Product;
use App\Module\TradeIn\Application\DTO\TradeInInput;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\User\Domain\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class TradeInService
{
    public function __construct(private TradeInPersistence $persistence, private TradeInEstimator $estimator, private TradeInNumberGenerator $numbers, private TradeInNotificationEmailService $notifications, private TradeInPrivateFileStorage $files)
    {
    }

    public function create(TradeInInput $input, ?User $user, ?Product $product, ?UploadedFile $rib = null): TradeInRequest
    {
        $estimate = $this->estimator->estimate($input, $product?->getPriceCents());
        $request = new TradeInRequest($this->numbers->generate(), $user, $input->firstName, $input->lastName, $input->email, $input->phone, $input->category, $input->productName, $input->purchasePriceCents, $input->purchaseYear, $input->brand, $input->model, $input->serialNumber, $input->conditionGrade, $input->functional, $input->hasAccessories, $input->hasProofOfPurchase, $input->description, $product?->getId(), $product?->getName(), $estimate['minCents'], $estimate['maxCents'], new \DateTimeImmutable());
        if (null !== $rib) {
            $stored = $this->files->storeRib($rib);
            $request->setRib($stored['path'], $stored['originalName'], $stored['size'], $stored['sha256']);
        }
        $this->persistence->save($request);
        $this->persistence->flush();
        $this->notifications->sendCreated($request);

        return $request;
    }

    public function setStatus(TradeInRequest $request, TradeInStatus $status): void
    {
        $previous = $request->getStatus();
        $this->assertTransition($request->getStatus(), $status);
        $request->setStatus($status);
        $this->persistence->save($request);
        $this->persistence->flush();
        if ($previous !== $status) {
            $this->notifications->sendStatusChanged($request);
        }
    }

    public function setOffer(TradeInRequest $request, int $offerCents, ?\DateTimeImmutable $expiresAt, ?string $note): void
    {
        $this->assertTransition($request->getStatus(), TradeInStatus::OFFER_SENT);
        $request->setOffer($offerCents, $expiresAt)->setAdminNote($note)->setStatus(TradeInStatus::OFFER_SENT);
        $this->persistence->save($request);
        $this->persistence->flush();
        $this->notifications->sendStatusChanged($request);
    }

    private function assertTransition(TradeInStatus $from, TradeInStatus $to): void
    {
        $allowed = match ($from) {
            TradeInStatus::SUBMITTED => [TradeInStatus::UNDER_REVIEW, TradeInStatus::CANCELLED, TradeInStatus::OFFER_SENT],
            TradeInStatus::UNDER_REVIEW => [TradeInStatus::OFFER_SENT, TradeInStatus::CANCELLED],
            TradeInStatus::OFFER_SENT => [TradeInStatus::ACCEPTED, TradeInStatus::DECLINED, TradeInStatus::EXPIRED, TradeInStatus::CANCELLED],
            TradeInStatus::ACCEPTED => [TradeInStatus::RECEIVED, TradeInStatus::CANCELLED],
            TradeInStatus::RECEIVED => [TradeInStatus::INSPECTED, TradeInStatus::CANCELLED],
            TradeInStatus::INSPECTED => [TradeInStatus::COMPLETED, TradeInStatus::CANCELLED],
            default => [],
        };
        if (!in_array($to, $allowed, true) && $from !== $to) {
            throw new \InvalidArgumentException(sprintf('Cette transition est impossible : « %s » vers « %s ».', $this->statusLabel($from), $this->statusLabel($to)));
        }
    }

    private function statusLabel(TradeInStatus $status): string
    {
        return match ($status) {
            TradeInStatus::SUBMITTED => 'Demande reçue',
            TradeInStatus::UNDER_REVIEW => 'En cours d’étude',
            TradeInStatus::OFFER_SENT => 'Offre envoyée',
            TradeInStatus::ACCEPTED => 'Offre acceptée',
            TradeInStatus::DECLINED => 'Offre refusée',
            TradeInStatus::RECEIVED => 'Matériel reçu',
            TradeInStatus::INSPECTED => 'Matériel inspecté',
            TradeInStatus::COMPLETED => 'Reprise terminée',
            TradeInStatus::CANCELLED => 'Demande annulée',
            TradeInStatus::EXPIRED => 'Offre expirée',
        };
    }
}
