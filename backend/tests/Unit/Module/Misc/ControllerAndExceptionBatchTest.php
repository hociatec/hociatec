<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Catalog\Exception\ProductFormRequestException;
use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Appointment\Application\Exception\InvalidAppointmentSlotException;
use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\UI\Controller\Client\ListAuditMetadataController;
use App\Module\Auth\UI\Controller\ActivationRedirectController;
use App\Module\Order\Application\Exception\CartCheckoutConflictException;
use App\Module\Order\Application\Exception\CartCheckoutNotFoundException;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Module\Quote\Infrastructure\Repository\ServiceOfferingRepository;
use App\Module\Quote\UI\Controller\PublicApi\GetServiceController;
use App\Module\Rating\Application\Exception\ProductReviewException;
use App\Module\Training\Application\Exception\TrainingSessionUnavailableException;
use App\Module\Training\Application\Projection\TrainingCategoryFormatter;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\UI\Controller\Admin\ListTrainingCategoriesController as AdminListTrainingCategoriesController;
use App\Module\Training\UI\Controller\PublicApi\ListTrainingCategoriesController as PublicListTrainingCategoriesController;
use App\Module\User\Application\Exception\ActivationEmailDeliveryException;
use App\Module\User\Application\Exception\InvalidBirthDateException;
use App\Module\User\Application\Exception\InvalidCurrentPasswordException;
use App\Module\User\Application\Exception\InvalidProfilePasswordException;
use App\Module\User\Application\Exception\UserAlreadyExistsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ControllerAndExceptionBatchTest extends TestCase
{
    public function testApiProblemAndDomainExceptionsExposeExpectedMessages(): void
    {
        $productForm = new ProductFormRequestException('Invalid product payload', 422);
        self::assertSame(422, $productForm->getStatusCode());
        self::assertSame('Invalid product payload', $productForm->getMessage());

        self::assertSame(404, (new OperationsResourceNotFoundException('missing'))->getStatusCode());
        self::assertSame(422, (new InvalidAppointmentSlotException('slot'))->getStatusCode());
        self::assertSame(409, (new CartCheckoutConflictException('conflict'))->getStatusCode());
        self::assertSame(404, (new CartCheckoutNotFoundException('missing'))->getStatusCode());
        self::assertSame(422, (new ProductReviewException('review'))->getStatusCode());
        self::assertSame(409, (new TrainingSessionUnavailableException('busy'))->getStatusCode());

        $activation = ActivationEmailDeliveryException::deliveryFailed(new \RuntimeException('smtp'));
        self::assertSame(503, $activation->getStatusCode());
        self::assertSame("L'e-mail d'activation n'a pas pu etre envoye.", $activation->getMessage());

        self::assertSame('La date de naissance est invalide.', InvalidBirthDateException::invalid()->getMessage());
        self::assertSame('La date de naissance ne peut pas etre dans le futur.', InvalidBirthDateException::inFuture()->getMessage());
        self::assertSame('Le mot de passe actuel est obligatoire pour cette modification.', InvalidCurrentPasswordException::missing()->getMessage());
        self::assertSame('Le mot de passe actuel est incorrect.', InvalidCurrentPasswordException::invalid()->getMessage());
        self::assertSame('Le nouveau mot de passe ne peut pas etre vide.', InvalidProfilePasswordException::empty()->getMessage());
        self::assertSame('Un utilisateur existe deja avec l\'adresse e-mail "ada@example.com".', UserAlreadyExistsException::forEmail('ada@example.com')->getMessage());
        self::assertSame(409, UserAlreadyExistsException::forEmail('ada@example.com')->getStatusCode());
    }

    public function testActivationRedirectControllerBuildsFrontendUrl(): void
    {
        $response = (new ActivationRedirectController('https://front.example.com/'))('__token__');

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://front.example.com/activation/__token__', $response->getTargetUrl());
    }

    public function testListAuditMetadataControllerReturnsTypesAndStatuses(): void
    {
        $response = (new ListAuditMetadataController(new AuditMetadataFormatter()))();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('success', $payload['status']);
        self::assertNotEmpty($payload['data']['types']);
        self::assertNotEmpty($payload['data']['statuses']);
    }

    public function testTrainingCategoryControllersFormatItems(): void
    {
        $repository = $this->createMock(TrainingCategoryRepository::class);
        $formatter = new TrainingCategoryFormatter();
        $category = new TrainingCategory('Infra', 'infra');
        $category->setPosition(3)->setIsActive(false);
        $this->setId($category, 12);

        $repository->expects(self::once())->method('findOrdered')->with(true)->willReturn([$category]);
        $publicResponse = (new PublicListTrainingCategoriesController($repository, $formatter))();
        $publicPayload = json_decode((string) $publicResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(12, $publicPayload['data']['items'][0]['id']);
        self::assertFalse($publicPayload['data']['items'][0]['isActive']);

        $repository2 = $this->createMock(TrainingCategoryRepository::class);
        $repository2->expects(self::once())->method('findOrdered')->with(null)->willReturn([$category]);
        $adminResponse = (new AdminListTrainingCategoriesController($repository2, $formatter))();
        $adminPayload = json_decode((string) $adminResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('infra', $adminPayload['data']['items'][0]['slug']);
        self::assertSame(3, $adminPayload['data']['items'][0]['position']);
    }

    public function testGetServiceControllerHandlesFoundAndNotFound(): void
    {
        $repository = $this->createMock(ServiceOfferingRepository::class);
        $repository->expects(self::exactly(2))
            ->method('find')
            ->willReturnCallback(function (int $id): ?ServiceOffering {
                return match ($id) {
                    99 => null,
                    7 => $this->serviceEntity(),
                    default => throw new \LogicException('Unexpected service id.'),
                };
            });

        $controller = new GetServiceController($repository, new \App\Module\Quote\Application\Projection\QuoteFormatter(new \App\Module\Quote\Application\Calculator\QuoteCalculator(), new \App\Module\Order\Application\Projection\OrderFormatter(new \App\Module\Rating\Application\Projection\ProductReviewFormatter(), new \App\Module\Order\Domain\Workflow\OrderStatusWorkflow())));

        $missing = $controller(99);
        $missingPayload = json_decode((string) $missing->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_NOT_FOUND, $missing->getStatusCode());
        self::assertSame('error', $missingPayload['status']);

        $found = $controller(7);
        $foundPayload = json_decode((string) $found->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_OK, $found->getStatusCode());
        self::assertSame('Audit SEO', $foundPayload['data']['title']);
        self::assertSame('2 heures', $foundPayload['data']['durationLabel']);
        self::assertSame(20, $foundPayload['data']['vatRate']);
    }

    private function serviceEntity(): ServiceOffering
    {
        $service = new ServiceOffering('Audit SEO', 15000, 2000);
        $service
            ->setDescription('Desc')
            ->setUnit('heure')
            ->setDurationValue(2)
            ->setDurationUnit('hour');
        $this->setId($service, 7);

        return $service;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
