<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth\Controller;

use App\Module\Auth\UI\Controller\ActivationRedirectController;
use PHPUnit\Framework\TestCase;

final class ActivationRedirectControllerTest extends TestCase
{
    public function testBuildsFrontendActivationUrl(): void
    {
        $response = (new ActivationRedirectController('https://front.example.com/'))('__token__');

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://front.example.com/activation/__token__', $response->getTargetUrl());
    }
}
