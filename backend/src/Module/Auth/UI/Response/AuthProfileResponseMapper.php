<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Response;

use App\Module\User\Application\Projection\UserProfileFormatter;
use App\Module\User\Domain\Entity\User;

final readonly class AuthProfileResponseMapper
{
    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'ROLE_ADMIN' => [
            'admin.access',
            'appointments.manage',
            'audits.manage',
            'backups.manage',
            'beta.manage',
            'catalog.manage',
            'customers.manage',
            'loyalty.manage',
            'marketing.manage',
            'news.manage',
            'news.comments.moderate',
            'operations.manage',
            'orders.manage',
            'payments.manage',
            'promotions.manage',
            'quotes.manage',
            'trade_ins.manage',
            'training.manage',
            'vouchers.manage',
        ],
        'ROLE_USER' => ['account.read', 'account.update'],
        'ROLE_APPOINTMENTS_MANAGER' => ['admin.access', 'appointments.manage'],
        'ROLE_AUDITS_MANAGER' => ['admin.access', 'audits.manage'],
        'ROLE_BACKUP_MANAGER' => ['admin.access', 'backups.manage'],
        'ROLE_BETA_MANAGER' => ['admin.access', 'beta.manage'],
        'ROLE_CATALOG_MANAGER' => ['admin.access', 'catalog.manage'],
        'ROLE_CUSTOMERS_MANAGER' => ['admin.access', 'customers.manage'],
        'ROLE_LOYALTY_MANAGER' => ['admin.access', 'loyalty.manage'],
        'ROLE_MARKETING_MANAGER' => ['admin.access', 'marketing.manage'],
        'ROLE_NEWS_MANAGER' => ['admin.access', 'news.manage', 'news.comments.moderate'],
        'ROLE_OPERATIONS' => ['admin.access', 'operations.manage'],
        'ROLE_ORDERS_MANAGER' => ['admin.access', 'orders.manage'],
        'ROLE_PAYMENTS_MANAGER' => ['admin.access', 'payments.manage'],
        'ROLE_PROMOTIONS_MANAGER' => ['admin.access', 'promotions.manage'],
        'ROLE_QUOTES_MANAGER' => ['admin.access', 'quotes.manage'],
        'ROLE_TRADE_INS_MANAGER' => ['admin.access', 'trade_ins.manage'],
        'ROLE_TRAINING_MANAGER' => ['admin.access', 'training.manage'],
        'ROLE_VOUCHERS_MANAGER' => ['admin.access', 'vouchers.manage'],
    ];

    public function __construct(private UserProfileFormatter $profiles)
    {
    }

    /** @return array<string, mixed> */
    public function anonymous(): array
    {
        return ['authenticated' => false];
    }

    /** @return array<string, mixed> */
    public function authenticated(User $user): array
    {
        return ['authenticated' => true]
            + $this->profiles->format($user)
            + [
                'permissions' => $this->permissions($user),
                'communicationPreferences' => $user->getCommunicationPreferences(),
            ];
    }

    /** @return list<string> */
    private function permissions(User $user): array
    {
        $permissions = [];
        foreach ($user->getRoles() as $role) {
            foreach (self::ROLE_PERMISSIONS[$role] ?? [] as $permission) {
                $permissions[$permission] = true;
            }
        }

        return array_keys($permissions);
    }
}
