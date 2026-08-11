<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Marketing\Handler\CreateEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Handler\DeleteEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Handler\UpdateEmailTemplateHandler;
use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
use App\Module\Admin\UI\Payment\Controller\ListPaymentMetadataController;
use App\Module\Audit\Application\Workflow\AuditEventLogger;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Order\Application\Workflow\OrderWorkflowService;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;

final class PaymentMetadataAndWorkflowServicesTest extends MiscSupportTestCase
{
    public function testPaymentMetadataControllerWorkflowAndManagers(): void
    {
        $controller = new ListPaymentMetadataController(new AdminPaymentFormatter());
        $payload = json_decode((string) $controller()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            ['value' => 'open', 'label' => 'Ouvert'],
            ['value' => 'paid', 'label' => 'Payé'],
            ['value' => 'failed', 'label' => 'Échoué'],
            ['value' => 'expired', 'label' => 'Expiré'],
        ], $payload['data']['statuses']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))->method('persist');
        $entityManager->expects(self::exactly(6))->method('flush');
        $entityManager->expects(self::once())->method('remove');

        $template = new EmailTemplate('Welcome', 'welcome', 'account_created', 'Sujet', '<p>Hi</p>', 'Hi');
        $persistence = new DoctrineUnitOfWork($entityManager);
        (new CreateEmailTemplateHandler($persistence))->create($template);
        (new UpdateEmailTemplateHandler($persistence))->update($template);
        (new DeleteEmailTemplateHandler($persistence))->delete($template);

        $user = $this->user();
        $audit = new AuditRequest('AUD-1', $user, AuditType::SEO, 'https://example.test', 'Goals');
        $logger = new AuditEventLogger(new DoctrineUnitOfWork($entityManager));
        $logger->log($audit, $user, 'created', 'Audit created');
        $logger->save(new \stdClass());

        $order = new Order('ORD-1', $user);
        $workflow = new OrderWorkflowService(new DoctrineUnitOfWork($entityManager));
        $workflow->cancel($order);
        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        self::assertSame(Order::INVOICE_STATUS_CANCELLED, $order->getInvoiceStatus());
    }
}
