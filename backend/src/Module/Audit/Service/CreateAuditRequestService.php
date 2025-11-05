<?php

declare(strict_types=1);

namespace App\Module\Audit\Service;

use App\Module\Audit\Entity\AuditChecklistItem;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CreateAuditRequestService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditRequestRepository $repository,
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

        $this->em->persist($audit);
        $this->em->flush();

        return $audit;
    }
}
