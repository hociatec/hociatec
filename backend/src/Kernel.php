<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        parent::boot();

        if (class_exists(\Doctrine\Deprecations\Deprecation::class)) {
            \Doctrine\Deprecations\Deprecation::ignoreDeprecations('https://github.com/doctrine/dbal/issues/5784');
        }
    }
}
