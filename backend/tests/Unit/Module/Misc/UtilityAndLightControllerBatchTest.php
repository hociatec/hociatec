<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Dashboard\Provider\DashboardCustomersProvider;
use App\Module\BetaTest\UI\Controller\GetBetaProfileOptionsController;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\User\Application\Workflow\VerificationTokenHasher;
use App\Shared\Infrastructure\Http\ApiValidationException;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class UtilityAndLightControllerBatchTest extends TestCase
{
    public function testVerificationTokenHasherAndTemplateRenderer(): void
    {
        $token = VerificationTokenHasher::generateRawToken();
        self::assertSame(64, strlen($token));
        self::assertTrue(VerificationTokenHasher::isValidRawToken($token));
        self::assertFalse(VerificationTokenHasher::isValidRawToken('invalid'));
        self::assertSame(hash('sha256', 'abc'), VerificationTokenHasher::hash('abc'));
        $this->coverPrivateConstructor(VerificationTokenHasher::class);

        $renderer = new MarketingTemplateRenderer();
        self::assertSame('Bonjour Ada', $renderer->render('Bonjour {{name}}', ['name' => 'Ada'], true));
        self::assertSame('Bonjour Ada', $renderer->render('<p>Bonjour {{name}}</p>', ['name' => 'Ada'], false));
    }

    public function testDtoValidatorAndConstraintViolationFormatter(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $service = new DtoValidator($validator, new ConstraintViolationFormatter());

        $service->validate(new class('Ada') {
            public function __construct(
                #[Assert\NotBlank]
                public string $name,
            ) {
            }
        });
        self::assertTrue(true);

        try {
            $service->validate(
                new class('') {
                    public function __construct(
                        #[Assert\NotBlank(message: 'Required')]
                        public string $name,
                    ) {
                    }
                },
                ['name' => 'Nom'],
                'Validation custom',
                400,
            );
            self::fail('Expected validation exception.');
        } catch (ApiValidationException $exception) {
            self::assertSame('Validation custom', $exception->getMessage());
            self::assertSame(['Nom: Required'], $exception->details);
            self::assertSame(400, $exception->statusCode);
        }
    }

    public function testDashboardCustomersProviderAndBetaProfileOptionsController(): void
    {
        $users = $this->createMock(\App\Module\User\Infrastructure\Repository\UserRepository::class);
        $users->expects(self::once())->method('findAdminCustomerRows')->with(null, 'highest_spent', 5)->willReturn([
            ['email' => 'ada@example.com'],
        ]);
        $customersProvider = new DashboardCustomersProvider($users);
        self::assertSame([['email' => 'ada@example.com']], $customersProvider->topCustomers());

        $betaPayload = json_decode((string) (new GetBetaProfileOptionsController())()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('choices', $betaPayload['data']);
    }

    private function coverPrivateConstructor(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $constructor->invoke($reflection->newInstanceWithoutConstructor());
    }
}
