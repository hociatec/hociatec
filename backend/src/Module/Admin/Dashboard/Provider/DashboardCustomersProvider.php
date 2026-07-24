<?php

declare(strict_types=1);

namespace App\Module\Admin\Dashboard\Provider;

use App\Module\User\Repository\UserRepository;

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
