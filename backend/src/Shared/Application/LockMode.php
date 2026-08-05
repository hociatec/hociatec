<?php

declare(strict_types=1);

namespace App\Shared\Application;

enum LockMode
{
    case NONE;
    case OPTIMISTIC;
    case PESSIMISTIC_READ;
    case PESSIMISTIC_WRITE;
}
