<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Marketing\Controller\DeleteTemplateController;
use App\Module\Admin\Application\Marketing\Handler\DeleteEmailTemplateHandler;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class DeleteTemplateControllerTest extends TestCase
{
    public function testDeleteTemplateController(): void
    {
        $template = new EmailTemplate('Welcome', 'welcome', 'account_created', 'Sujet', '<p>Hi</p>');
        $this->setId($template, 4);

        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls(null, $template);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($template);
        $entityManager->expects(self::once())->method('flush');

        $controller = new DeleteTemplateController(
            new DeleteEmailTemplateHandler(new DoctrineUnitOfWork($entityManager)),
            $templates,
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $controller(4)->getStatusCode());
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
