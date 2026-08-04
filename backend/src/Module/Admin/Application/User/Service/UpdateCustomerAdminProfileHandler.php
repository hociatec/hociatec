<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\User\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\Admin\Application\User\DTO\CustomerAdminProfileInput;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;

final readonly class UpdateCustomerAdminProfileHandler
{
    public function __construct(
        private UserRepository $users,
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
