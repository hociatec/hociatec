<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Marketing\Controller;

use App\Module\Admin\UI\Marketing\Controller\CreateTemplateController;
use App\Module\Admin\UI\Marketing\Controller\DeleteTemplateController;
use App\Module\Admin\UI\Marketing\Controller\GetTemplateController;
use App\Module\Admin\UI\Marketing\Controller\ListCampaignsController;
use App\Module\Admin\UI\Marketing\Controller\ListSegmentsController;
use App\Module\Admin\UI\Marketing\Controller\ListTemplatesController;
use App\Module\Admin\UI\Marketing\Controller\UpdateTemplateController;
use App\Module\Admin\Application\Marketing\Service\EmailTemplateAdminManager;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRepository;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Service\EmailTemplateScenarioProvider;
use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class AdminMarketingControllersTest extends TestCase
{
    public function testTemplateControllersCoverCreateListGetUpdateAndDelete(): void
    {
        $scenarioProvider = new EmailTemplateScenarioProvider();
        $scenarioKey = array_key_first($scenarioProvider->getTemplateScenarioDefinitions());
        self::assertIsString($scenarioKey);
        $template = new EmailTemplate('Welcome', 'welcome', $scenarioKey, 'Subject', '<p>Hello</p>', 'Hello');
        $this->setId($template, 10);

        $templates = $this->createMock(EmailTemplateRepository::class);
        $templates->method('findBy')->willReturn([$template]);
        $templates->method('count')->willReturn(1);
        $templates->method('find')->willReturnCallback(static fn (int $id): ?EmailTemplate => 10 === $id ? $template : null);
        $templates->method('findOneBySlug')->willReturnCallback(static fn (string $slug): ?EmailTemplate => 'duplicate' === $slug ? new EmailTemplate('Duplicate', 'duplicate', $scenarioKey, 'S', '<p>H</p>', null) : null);

        $manager = $this->manager();
        $validator = $this->validator();

        $listPayload = $this->payload((new ListTemplatesController($templates))(Request::create('/?page=1&perPage=5')));
        self::assertSame('welcome', $listPayload['data']['items'][0]['slug']);
        self::assertSame(1, $listPayload['data']['meta']['total']);

        self::assertSame(404, (new GetTemplateController($templates))(999)->getStatusCode());
        $getPayload = $this->payload((new GetTemplateController($templates))(10));
        self::assertSame('Welcome', $getPayload['data']['template']['name']);

        $create = new CreateTemplateController($manager, $templates, $scenarioProvider, $validator);
        self::assertSame(400, $create($this->jsonRequest([
            'name' => 'New',
            'slug' => 'new',
            'scenarioKey' => 'missing',
            'subjectTemplate' => 'Subject',
            'htmlBody' => '<p>Hello</p>',
        ]))->getStatusCode());
        self::assertSame(400, $create($this->jsonRequest([
            'name' => 'New',
            'slug' => 'duplicate',
            'scenarioKey' => $scenarioKey,
            'subjectTemplate' => 'Subject',
            'htmlBody' => '<p>Hello</p>',
        ]))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest([
            'name' => 'New',
            'slug' => 'new',
            'scenarioKey' => $scenarioKey,
            'subjectTemplate' => 'Subject',
            'htmlBody' => '<p>Hello</p>',
            'textBody' => 'Hello',
            'isActive' => false,
        ]))->getStatusCode());

        $update = new UpdateTemplateController($manager, $templates, $scenarioProvider, $validator);
        self::assertSame(404, $update(999, $this->jsonRequest([]))->getStatusCode());
        $updated = $update(10, $this->jsonRequest([
            'name' => 'Updated',
            'slug' => 'updated',
            'scenarioKey' => $scenarioKey,
            'subjectTemplate' => 'Subject 2',
            'htmlBody' => '<p>Updated</p>',
            'textBody' => null,
            'isActive' => true,
        ], 'PUT'));
        self::assertSame(200, $updated->getStatusCode());
        self::assertSame('Updated', $this->payload($updated)['data']['template']['name']);

        self::assertSame(404, (new DeleteTemplateController($manager, $templates))(999)->getStatusCode());
        $deleted = (new DeleteTemplateController($manager, $templates))(10);
        self::assertSame(200, $deleted->getStatusCode());
        self::assertTrue($this->payload($deleted)['data']['deleted']);
    }

    public function testSegmentsAndCampaignListControllersFormatPayloads(): void
    {
        $scenarioProvider = new EmailTemplateScenarioProvider();
        foreach (['templates', 'campaigns', 'transactional', 'unknown'] as $type) {
            $payload = $this->payload((new ListSegmentsController($scenarioProvider))(Request::create('/?type='.$type)));
            self::assertNotSame([], $payload['data']['items']);
        }

        $template = new EmailTemplate('Digest', 'digest', 'newsletter', 'Subject', '<p>Hello</p>', null);
        $this->setId($template, 7);
        $campaign = new EmailCampaign('Campaign', 'all_customers', ['vip' => true], 'Subject', '<p>Hello</p>', null, 3, 'admin@example.test', $template);
        $this->setId($campaign, 8);

        $campaigns = $this->createMock(EmailCampaignRepository::class);
        $campaigns->expects(self::once())->method('findBy')->with([], ['sentAt' => 'DESC'], 10, 10)->willReturn([$campaign]);
        $campaigns->expects(self::once())->method('count')->with([])->willReturn(12);

        $payload = $this->payload((new ListCampaignsController($campaigns))(Request::create('/?page=2&perPage=10')));
        self::assertSame('Campaign', $payload['data']['items'][0]['name']);
        self::assertSame('Digest', $payload['data']['items'][0]['template']['name']);
        self::assertSame(12, $payload['data']['meta']['total']);
    }

    private function manager(): EmailTemplateAdminManager
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return new EmailTemplateAdminManager(new DoctrineUnitOfWork($entityManager));
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create('/', $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
