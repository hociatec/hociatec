<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\Service;

use App\Module\Admin\Application\User\DTO\CustomerAdminProfileInput;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final readonly class UpdateCustomerAdminProfileHandler
{
    public function __construct(
        private UserRepositoryPort $users,
        private DoctrineUnitOfWork $unitOfWork,
    ) {
    }

    public function update(User $user, CustomerAdminProfileInput $input): void
    {
        $user
            ->setAdminNotes('' !== $input->adminNotes ? $input->adminNotes : null)
            ->setAdminTags($input->adminTags);

        $this->users->save($user);
        $this->unitOfWork->commit();
    }
}
