<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class CsrfExempt
{
}
