<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Service;

use App\Module\Catalog\Entity\Product;
use App\Module\TradeIn\DTO\TradeInInput;
use App\Module\TradeIn\Entity\TradeInRequest;
use App\Module\TradeIn\Enum\TradeInStatus;
use App\Module\User\Entity\User;

final readonly class TradeInService
{
    public function __construct(private TradeInPersistence $persistence, private TradeInEstimator $estimator, private TradeInNumberGenerator $numbers) {}

    public function create(TradeInInput $input, ?User $user, ?Product $product): TradeInRequest
    {
        $estimate = $this->estimator->estimate($input, $product?->getPriceCents());
        $request = new TradeInRequest($this->numbers->generate(), $user, $input->firstName, $input->lastName, $input->email, $input->phone, $input->category, $input->productName, $input->brand, $input->model, $input->serialNumber, $input->conditionGrade, $input->functional, $input->hasAccessories, $input->hasProofOfPurchase, $input->description, $product?->getId(), $product?->getName(), $estimate['minCents'], $estimate['maxCents'], new \DateTimeImmutable());
        $this->persistence->save($request);

        return $request;
    }

    public function setStatus(TradeInRequest $request, TradeInStatus $status): void
    {
        $request->setStatus($status);
        $this->persistence->save($request);
    }

    public function setOffer(TradeInRequest $request, int $offerCents, ?\DateTimeImmutable $expiresAt, ?string $note): void
    {
        $request->setOffer($offerCents, $expiresAt)->setAdminNote($note)->setStatus(TradeInStatus::OFFER_SENT);
        $this->persistence->save($request);
    }
}
