<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Controller;

use App\Module\Training\Application\Projection\TrainingCategoryFormatter;
use App\Module\Training\Domain\Entity\TrainingCategory;
use App\Module\Training\Infrastructure\Repository\TrainingCategoryRepository;
use App\Module\Training\UI\Controller\Admin\ListTrainingCategoriesController as AdminListTrainingCategoriesController;
use App\Module\Training\UI\Controller\PublicApi\ListTrainingCategoriesController as PublicListTrainingCategoriesController;
use PHPUnit\Framework\TestCase;

final class TrainingCategoryControllersTest extends TestCase
{
    public function testPublicAndAdminControllersFormatItems(): void
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

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
