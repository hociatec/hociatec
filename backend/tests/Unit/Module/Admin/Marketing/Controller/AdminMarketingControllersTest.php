<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Marketing\Controller;

use App\Module\Admin\Application\Marketing\Handler\CreateEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Handler\DeleteEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Handler\UpdateEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Writer\EmailTemplateWriter;
use App\Module\Admin\UI\Marketing\Controller\PreviewAudienceController;
use App\Module\Admin\UI\Marketing\Controller\SendCampaignController;
use App\Module\Admin\UI\Marketing\Controller\CreateTemplateController;
use App\Module\Admin\UI\Marketing\Controller\DeleteTemplateController;
use App\Module\Admin\UI\Marketing\Controller\GetTemplateController;
use App\Module\Admin\UI\Marketing\Controller\ListCampaignsController;
use App\Module\Admin\UI\Marketing\Controller\ListSegmentsController;
use App\Module\Admin\UI\Marketing\Controller\ListTemplatesController;
use App\Module\Admin\UI\Marketing\Controller\UpdateTemplateController;
use App\Module\Admin\UI\Marketing\Http\MarketingRequestMapper;
use App\Module\Marketing\Application\Notification\MarketingCampaignSender;
use App\Module\Marketing\Application\Outbox\PrepareMarketingCampaignHandler;
use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Marketing\Application\Port\MarketingAudienceQuery;
use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Application\Provider\EmailTemplateScenarioProvider;
use App\Module\Marketing\Application\Security\EmailTemplatePreviewSanitizer;
use App\Module\Marketing\Application\Workflow\MarketingCampaignService;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignContentSnapshot;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRepository;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;
use App\Module\Marketing\Application\Projection\EmailCampaignResponseFormatter;
use App\Module\Marketing\Application\Projection\EmailTemplateResponseFormatter;
use App\Module\Outbox\Application\Outbox;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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

        $persistence = new DoctrineUnitOfWork($this->createMock(EntityManagerInterface::class));
        $createTemplateHandler = new CreateEmailTemplateHandler($persistence);
        $updateTemplateHandler = new UpdateEmailTemplateHandler($persistence);
        $deleteTemplateHandler = new DeleteEmailTemplateHandler($persistence);
        $writer = new EmailTemplateWriter($createTemplateHandler, $updateTemplateHandler, $templates, $scenarioProvider);
        $validator = $this->validator();
        $requestMapper = new MarketingRequestMapper();
        $templateFormatter = new EmailTemplateResponseFormatter(new EmailTemplatePreviewSanitizer());

        $listPayload = $this->payload((new ListTemplatesController($templates, $templateFormatter))(Request::create('/?page=1&perPage=5')));
        self::assertSame('welcome', $listPayload['data']['items'][0]['slug']);
        self::assertSame(1, $listPayload['data']['meta']['total']);

        self::assertSame(404, (new GetTemplateController($templates, $templateFormatter))(999)->getStatusCode());
        $getPayload = $this->payload((new GetTemplateController($templates, $templateFormatter))(10));
        self::assertSame('Welcome', $getPayload['data']['template']['name']);

        $create = new CreateTemplateController($writer, $validator, $requestMapper, $templateFormatter);
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

        $update = new UpdateTemplateController($templates, $writer, $validator, $requestMapper, $templateFormatter);
        self::assertSame(404, $update(999, $this->jsonRequest([]))->getStatusCode());
        self::assertSame(400, $update(10, $this->jsonRequest([
            'name' => 'Updated',
            'slug' => 'duplicate',
            'scenarioKey' => $scenarioKey,
            'subjectTemplate' => 'Subject duplicate',
            'htmlBody' => '<p>Updated</p>',
            'textBody' => null,
            'isActive' => true,
        ], 'PUT'))->getStatusCode());
        self::assertSame(400, $update(10, $this->jsonRequest([
            'name' => 'Updated',
            'slug' => 'updated',
            'scenarioKey' => 'missing',
            'subjectTemplate' => 'Subject invalid scenario',
            'htmlBody' => '<p>Updated</p>',
            'textBody' => null,
            'isActive' => true,
        ], 'PUT'))->getStatusCode());
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

        self::assertSame(404, (new DeleteTemplateController($deleteTemplateHandler, $templates))(999)->getStatusCode());
        $deleted = (new DeleteTemplateController($deleteTemplateHandler, $templates))(10);
        self::assertSame(200, $deleted->getStatusCode());
        self::assertTrue($this->payload($deleted)['data']['deleted']);
    }

    public function testTemplateFormatterIncludesSanitizedPreviewHtml(): void
    {
        $template = new EmailTemplate(
            'Security',
            'security',
            'newsletter',
            'Subject',
            '<p onclick="alert(1)">Hello</p><a href="https://example.test">Pay</a><img src="https://tracker.test/pixel.png" alt="Pixel"><form><input name="email"></form><script>alert(1)</script>',
            null,
        );

        $payload = (new EmailTemplateResponseFormatter(new EmailTemplatePreviewSanitizer()))->format($template);

        self::assertSame($template->getHtmlBody(), $payload['htmlBody']);
        self::assertStringContainsString('<p>Hello</p>', $payload['previewHtmlBody']);
        self::assertStringContainsString('<a>Pay</a>', $payload['previewHtmlBody']);
        self::assertStringContainsString('<img alt="Pixel">', $payload['previewHtmlBody']);
        self::assertStringNotContainsString('onclick', $payload['previewHtmlBody']);
        self::assertStringNotContainsString('href=', $payload['previewHtmlBody']);
        self::assertStringNotContainsString('src=', $payload['previewHtmlBody']);
        self::assertStringNotContainsString('<form', $payload['previewHtmlBody']);
        self::assertStringNotContainsString('<script', $payload['previewHtmlBody']);
    }

    public function testSegmentsAndCampaignListControllersFormatPayloads(): void
    {
        $scenarioProvider = new EmailTemplateScenarioProvider();
        foreach (['templates', 'campaigns', 'transactional', 'unknown'] as $type) {
            $payload = $this->payload((new ListSegmentsController($scenarioProvider))(Request::create('/?type='.$type)));
            self::assertNotSame([], $payload['data']['items']);
        }

        $requestMapper = new MarketingRequestMapper();
        self::assertSame(
            'vip',
            $requestMapper->audience($this->jsonRequest(['segmentKey' => 'vip', 'criteria' => []]))->segmentKey
        );
        self::assertSame(
            'Campaign',
            $requestMapper->campaign($this->jsonRequest(['name' => 'Campaign', 'segmentKey' => 'all_customers', 'subject' => 'Subject', 'htmlBody' => '<p>Hello</p>']))->name
        );

        $template = new EmailTemplate('Digest', 'digest', 'newsletter', 'Subject', '<p>Hello</p>', null);
        $this->setId($template, 7);
        $campaign = new EmailCampaign('Campaign', 'all_customers', ['vip' => true], new EmailCampaignContentSnapshot('Subject', '<p>Hello</p>'), 3, 'admin@example.test', $template);
        $this->setId($campaign, 8);

        $campaigns = $this->createMock(EmailCampaignRepository::class);
        $campaigns->expects(self::once())->method('findBy')->with([], ['sentAt' => 'DESC'], 10, 10)->willReturn([$campaign]);
        $campaigns->expects(self::once())->method('count')->with([])->willReturn(12);

        $payload = $this->payload((new ListCampaignsController($campaigns, new EmailCampaignResponseFormatter()))(Request::create('/?page=2&perPage=10')));
        self::assertSame('Campaign', $payload['data']['items'][0]['name']);
        self::assertSame('Digest', $payload['data']['items'][0]['template']['name']);
        self::assertSame(12, $payload['data']['meta']['total']);
    }

    public function testPreviewAndSendCampaignControllersCoverAudienceAndActorBranches(): void
    {
        $scenarioProvider = new EmailTemplateScenarioProvider();
        $recipient = new User('vip@example.test', 'Grace', 'Hopper', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $actor = new User('manager@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($recipient, 21);
        $this->setId($actor, 22);

        $audiences = new class($recipient) implements MarketingAudienceQuery {
            public function __construct(private readonly User $recipient)
            {
            }

            public function resolveRecipients(string $segmentKey, array $criteria, ?int $limit = null, int $offset = 0): array
            {
                return [$this->recipient];
            }

            public function resolveRecipientsAfterId(string $segmentKey, array $criteria, int $lastUserId, int $limit): array
            {
                return [];
            }
        };
        $service = new MarketingCampaignService(
            new MarketingAudienceProvider($audiences, $scenarioProvider),
            new MarketingCampaignSender(
                new class implements UnitOfWork {
                    public function persist(object $entity): void
                    {
                        (new \ReflectionObject($entity))->getProperty('id')->setValue($entity, 77);
                    }
                    public function remove(object $entity): void {}
                    public function flush(): void {}
                },
                new class implements TransactionManager {
                    public function transactional(\Closure $operation): mixed
                    {
                        return $operation();
                    }
                },
                new Outbox($this->createMock(UnitOfWork::class)),
            ),
        );

        $preview = new PreviewAudienceController($service, $this->validator(), new MarketingRequestMapper());
        $previewPayload = $this->payload($preview($this->jsonRequest([
            'segmentKey' => 'all_verified_users',
            'criteria' => [],
        ])));
        self::assertSame(1, $previewPayload['data']['preview']['count']);
        self::assertSame('vip@example.test', $previewPayload['data']['preview']['recipients'][0]['email']);
        self::assertArrayHasKey('all_verified_users', $previewPayload['data']['segments']);

        $template = new EmailTemplate('Digest', 'digest', 'newsletter', 'Subject', '<p>Hello</p>', 'Hello');
        $this->setId($template, 7);
        $templates = $this->createMock(EmailTemplateRepositoryPort::class);
        $templates->method('find')->willReturn($template);
        $templates->method('findBy')->willReturn([]);
        $templates->method('count')->willReturn(0);
        $templates->method('findOneBySlug')->willReturn(null);
        $templates->method('findActiveOneByScenarioKey')->willReturn(null);

        $send = new SendCampaignController($service, $templates, $this->validator(), new MarketingRequestMapper(), new EmailCampaignResponseFormatter());
        $send->setContainer($this->controllerContainer($actor));
        $sendResponse = $send($this->jsonRequest([
            'name' => 'Back to school',
            'segmentKey' => 'all_verified_users',
            'criteria' => ['vip' => true],
            'subject' => 'Nouvelle campagne',
            'htmlBody' => '<p>Hello</p>',
            'textBody' => 'Hello',
            'templateId' => 7,
        ]));
        self::assertSame(Response::HTTP_CREATED, $sendResponse->getStatusCode());
        $sendPayload = $this->payload($sendResponse);
        self::assertSame(77, $sendPayload['data']['campaign']['id']);
        self::assertSame('Back to school', $sendPayload['data']['campaign']['name']);
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

    private function controllerContainer(?User $user): Container
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));
        }
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }
}
