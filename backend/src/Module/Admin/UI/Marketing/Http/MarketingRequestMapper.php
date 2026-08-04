<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Marketing\Http;

use App\Infrastructure\Http\JsonPayload;
use App\Module\Admin\Application\Marketing\DTO\MarketingAudienceInput;
use App\Module\Admin\Application\Marketing\DTO\MarketingCampaignInput;
use App\Module\Admin\Application\Marketing\DTO\MarketingTemplateInput;
use Symfony\Component\HttpFoundation\Request;

final readonly class MarketingRequestMapper
{
    public function template(Request $request): MarketingTemplateInput
    {
        return MarketingTemplateInput::fromArray(JsonPayload::decode($request));
    }

    public function audience(Request $request): MarketingAudienceInput
    {
        return MarketingAudienceInput::fromArray(JsonPayload::decode($request));
    }

    public function campaign(Request $request): MarketingCampaignInput
    {
        return MarketingCampaignInput::fromArray(JsonPayload::decode($request));
    }
}
