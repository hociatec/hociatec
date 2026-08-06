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

    public function __construct(mixed ...$values)
    {
        $data = $this->mapValues($values);
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

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function mapValues(array $values): array
    {
        $keys = ['title', 'description', 'billingMode', 'durationValue', 'durationUnit', 'priceCents', 'vatRateBps', 'isFeaturedHome', 'imageFile', 'imageUrl', 'imageAlt', 'updatesBillingMode', 'updatesDuration'];
        $defaults = array_fill_keys($keys, null);
        $defaults['title'] = '';
        $defaults['isFeaturedHome'] = false;
        $defaults['updatesBillingMode'] = false;
        $defaults['updatesDuration'] = false;
        foreach ($values as $index => $value) {
            if (!is_int($index)) {
                continue;
            }
            if (isset($keys[$index])) {
                $defaults[$keys[$index]] = $value;
            }
        }

        return array_replace($defaults, array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }
}
