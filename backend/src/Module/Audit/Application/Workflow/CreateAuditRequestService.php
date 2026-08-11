<?php

declare(strict_types=1);

namespace App\Module\Audit\Application\Workflow;

use App\Module\Audit\Application\Provider\AuditTemplateProvider;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

class CreateAuditRequestService
{
    public function __construct(
        private readonly UnitOfWork $persistence,
        private readonly AuditTemplateProvider $templates,
    ) {
    }

    public function generateNumber(): string
    {
        $date = (new \DateTimeImmutable())->format('Ymd');
        $rand = strtoupper(bin2hex(random_bytes(2)));

        return sprintf('AUD-%s-%s', $date, $rand);
    }

    public function create(User $user, AuditType $type, string $targetUrl, ?string $objectives): AuditRequest
    {
        $number = $this->generateNumber();
        $audit = new AuditRequest($number, $user, $type, $targetUrl, $objectives);

        $template = $this->templates->getTemplate($type);
        $pos = 1;
        foreach ($template as $category => $items) {
            foreach ($items as $item) {
                $row = new AuditChecklistItem($category, $item['key'], $item['label'], $pos++);
                if (isset($item['level'])) {
                    $row->setLevel($item['level']);
                }
                $audit->addItem($row);
            }
        }

        $this->persistence->persist($audit);
        $this->persistence->flush();

        return $audit;
    }
}
