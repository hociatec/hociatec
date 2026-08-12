<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Admin\Application\Operations\DTO\SupportRequestOutput;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\DTO\SupportUpdateData;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Application\Workflow\SupportRequestService;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Domain\Entity\User;

final readonly class SupportOperationsService
{
    public function __construct(
        private SupportRequestRepositoryPort $supportRequests,
        private UserRepositoryPort $users,
        private OrderRepositoryPort $orders,
        private SupportRequestService $supportService,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /** @return list<SupportRequestOutput> */
    public function list(int $limit = 20, int $offset = 0): array
    {
        return array_map($this->formatter->supportRequest(...), $this->supportRequests->findBy([], ['updatedAt' => 'DESC'], max(1, min(100, $limit)), max(0, $offset)));
    }

    public function count(): int
    {
        return $this->supportRequests->count([]);
    }

    public function create(SupportCreateData $data): SupportRequestOutput
    {
        $customer = $this->users->find($data->customerId);
        if (!$customer instanceof User) {
            throw new OperationsResourceNotFoundException('Client introuvable.');
        }

        $order = null !== $data->orderId ? $this->orders->find($data->orderId) : null;
        $support = $this->supportService->create($customer, $data, $order instanceof Order ? $order : null, []);

        return $this->formatter->supportRequest($support);
    }

    public function update(int $supportId, SupportUpdateData $data): SupportRequestOutput
    {
        $support = $this->findSupport($supportId);
        $support = $this->supportService->update($support, $data);

        return $this->formatter->supportRequest($support);
    }

    public function reply(int $supportId, SupportReplyData $data): SupportRequestOutput
    {
        $support = $this->findSupport($supportId);
        $support = $this->supportService->reply($support, $data);

        return $this->formatter->supportRequest($support);
    }

    public function show(int $supportId): SupportRequestOutput
    {
        return $this->formatter->supportRequest($this->findSupport($supportId));
    }

    private function findSupport(int $supportId): SupportRequest
    {
        $support = $this->supportRequests->find($supportId);
        if (!$support instanceof SupportRequest) {
            throw new OperationsResourceNotFoundException('Demande SAV introuvable.');
        }

        return $support;
    }
}
