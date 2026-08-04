<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Http;

use App\Module\Admin\Application\Marketing\DTO\MarketingAudienceInput;
use App\Module\Admin\Application\Marketing\DTO\MarketingCampaignInput;
use App\Module\Admin\Application\Marketing\DTO\MarketingTemplateInput;
use Symfony\Component\HttpFoundation\Request;

final readonly class MarketingRequestMapper
{
    public function template(Request $request): MarketingTemplateInput
    {
        return \App\Infrastructure\Http\JsonRequestInput::decode($request, MarketingTemplateInput::class);
    }

    public function audience(Request $request): MarketingAudienceInput
    {
        return \App\Infrastructure\Http\JsonRequestInput::decode($request, MarketingAudienceInput::class);
    }

    public function campaign(Request $request): MarketingCampaignInput
    {
        return \App\Infrastructure\Http\JsonRequestInput::decode($request, MarketingCampaignInput::class);
    }
}
