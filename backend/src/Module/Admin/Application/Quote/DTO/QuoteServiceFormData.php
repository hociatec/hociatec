<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Quote\DTO;

final readonly class QuoteServiceFormData
{
    public string $title;
    public ?string $description;
    public ?string $billingMode;
    public ?int $durationValue;
    public ?string $durationUnit;
    public ?int $priceCents;
    public ?int $vatRateBps;
    public bool $isFeaturedHome;
    public ?object $imageFile;
    public ?string $imageUrl;
    public ?string $imageAlt;
    public bool $updatesBillingMode;
    public bool $updatesDuration;

    /**
     * @param array{
     *   title?: string,
     *   description?: ?string,
     *   billingMode?: ?string,
     *   durationValue?: ?int,
     *   durationUnit?: ?string,
     *   priceCents?: ?int,
     *   vatRateBps?: ?int,
     *   isFeaturedHome?: bool,
     *   imageFile?: ?object,
     *   imageUrl?: ?string,
     *   imageAlt?: ?string,
     *   updatesBillingMode?: bool,
     *   updatesDuration?: bool
     * }|null $payload
     */
    public function __construct(?array $payload = null)
    {
        $data = array_replace([
            'title' => '',
            'description' => null,
            'billingMode' => null,
            'durationValue' => null,
            'durationUnit' => null,
            'priceCents' => null,
            'vatRateBps' => null,
            'isFeaturedHome' => false,
            'imageFile' => null,
            'imageUrl' => null,
            'imageAlt' => null,
            'updatesBillingMode' => false,
            'updatesDuration' => false,
        ], $payload ?? []);
        $this->title = (string) $data['title'];
        $this->description = $data['description'];
        $this->billingMode = $data['billingMode'];
        $this->durationValue = $data['durationValue'];
        $this->durationUnit = $data['durationUnit'];
        $this->priceCents = $data['priceCents'];
        $this->vatRateBps = $data['vatRateBps'];
        $this->isFeaturedHome = (bool) $data['isFeaturedHome'];
        $this->imageFile = $data['imageFile'];
        $this->imageUrl = $data['imageUrl'];
        $this->imageAlt = $data['imageAlt'];
        $this->updatesBillingMode = (bool) $data['updatesBillingMode'];
        $this->updatesDuration = (bool) $data['updatesDuration'];
    }

}
