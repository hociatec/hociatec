<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Dashboard\Provider;

use App\Module\User\Infrastructure\Repository\UserRepository;

final readonly class DashboardCustomersProvider
{
    public function __construct(private UserRepository $users)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topCustomers(): array
    {
        return $this->users->findAdminCustomerRows(null, 'highest_spent', 5);
    }
}
