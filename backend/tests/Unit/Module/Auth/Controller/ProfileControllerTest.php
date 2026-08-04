<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Auth\Controller;

use App\Module\Auth\UI\Controller\ProfileController;
use App\Module\Auth\UI\Response\AuthProfileResponseMapper;
use App\Module\User\Application\Projection\UserProfileFormatter;
use App\Module\User\Domain\Entity\ShippingAddress;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\ShippingAddressRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProfileControllerTest extends TestCase
{
    public function testItReturnsUnauthenticatedPayloadWhenNoUserIsPresent(): void
    {
        $repository = $this->createMock(ShippingAddressRepository::class);
        $repository->expects(self::never())->method('findDefaultForUser');
        $repository->expects(self::never())->method('findFirstForUser');

        $controller = new class(new AuthProfileResponseMapper(new UserProfileFormatter($repository))) extends ProfileController {
            protected function getUser(): ?UserInterface
            {
                return null;
            }
        };

        $payload = json_decode((string) $controller->__invoke()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('success', $payload['status']);
        self::assertSame(['authenticated' => false], $payload['data']);
    }

    public function testItReturnsAuthenticatedUserPayloadWithFallbackAddress(): void
    {
        $user = new User(
            'ada@example.com',
            'Ada',
            'Lovelace',
            new \DateTimeImmutable('1815-12-10'),
            '0102030405',
            'f',
        );
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCommunicationPreferences(['email', 'phone']);
        $this->setPrivateProperty($user, 'id', 42);

        $address = (new ShippingAddress($user, 'Ada Lovelace', '12 rue des Tests', '75001', 'Paris'))
            ->setIsDefault(true);

        $repository = $this->createMock(ShippingAddressRepository::class);
        $repository
            ->expects(self::once())
            ->method('findDefaultForUser')
            ->with($user)
            ->willReturn(null);
        $repository
            ->expects(self::once())
            ->method('findFirstForUser')
            ->with($user)
            ->willReturn($address);

        $controller = new class(new AuthProfileResponseMapper(new UserProfileFormatter($repository)), $user) extends ProfileController {
            public function __construct(AuthProfileResponseMapper $profiles, private readonly User $user)
            {
                parent::__construct($profiles);
            }

            protected function getUser(): ?UserInterface
            {
                return $this->user;
            }
        };

        $payload = json_decode((string) $controller->__invoke()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('success', $payload['status']);
        self::assertTrue($payload['data']['authenticated']);
        self::assertSame(42, $payload['data']['id']);
        self::assertSame('ada@example.com', $payload['data']['email']);
        self::assertSame('Ada', $payload['data']['firstName']);
        self::assertSame('Lovelace', $payload['data']['lastName']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $payload['data']['roles']);
        self::assertSame('12 rue des Tests', $payload['data']['address']);
        self::assertSame('75001', $payload['data']['postalCode']);
        self::assertSame('Paris', $payload['data']['city']);
        self::assertSame('1815-12-10', $payload['data']['birthDate']);
        self::assertSame('0102030405', $payload['data']['phoneNumber']);
        self::assertSame('f', $payload['data']['gender']);
        self::assertSame(['email', 'phone'], $payload['data']['communicationPreferences']);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }
}
